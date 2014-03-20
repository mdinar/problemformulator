<?php
App::uses('File', 'Utility');

// Controller/ProblemMapsController.php

class ProblemMapsController extends AppController {

    // used for XML and JSON output
    public $components = array(
        'RequestHandler'
    );

    // models used
    public $uses = array(
        'ProblemMap',
        'Decomposition',
        'LogEntry'
    );

    // determine if the file extension is prolog and if so set the appropriate layout
    public function beforeFilter() {

        parent::beforeFilter();
        $this->RequestHandler->setContent('pl', 'text/pl');
    }

    // check if the user is authorized
    public function isAuthorized($user = NULL) {


        // if they are viewing the list or adding problem maps
        // then they are allowed


        if (in_array($this->action, array(
            'index',
            'add'
        ))) {
            return true;
        }

        // The owner of a post can edit and delete it

        if (in_array($this->action, array(
            'view',
            'view_list',
            'view_graph',
            'edit',
            'delete',
            'view_log',
            'getInvalidEntities'
        ))) {
            $pmapId = $this->request->params['pass'][0];

            if ($this->ProblemMap->isOwnedBy($pmapId, $user['id'])) {
                return true;
            }
        }

        // this enables the admin to access everything
        return parent::isAuthorized($user);
    }

    // gets the invalid entities using ASP and the checker rules
    public function getInvalidEntities($id) {

        $ProblemMap = $this->ProblemMap->findById($id);

        // save problem map to view variables
        $this->set(compact('ProblemMap'));

        // and for XML / JSON display
        $this->set('_serialize', array(
            'ProblemMap'
        ));

        // create a new view
        $view = new View($this);

        // render the problem map as a prolog file
        $viewdata = $view->render('pl/view', 'pl/default', 'pl');

        //set the file name to save the View's output
        $path = WWW_ROOT . 'files/pl/' . $id . '.pl';
        $file = new File($path, true);

        //write the content to the file
        $file->write($viewdata);

        //echo 'clingo -n 0 ' . WWW_ROOT . 'files/pl/' . $id . '.pl' . " " . WWW_ROOT . 'pl/completeness_rules.pl';
        // call clingo on the file and get the invalid entities.

        //$invalid_string = shell_exec('clingo -n 0 ' . WWW_ROOT . 'files/pl/' . $id . '.pl' . " " . WWW_ROOT . 'pl/completeness_rules.pl' . " | grep 'invalid'");

        $invalid_string = shell_exec('ulimit -t 15; clingo -n 0 ' . WWW_ROOT . 'files/pl/' . $id . '.pl' . " " . WWW_ROOT . 'pl/completeness_rules.pl' . " | grep 'invalid'");

        //echo $invalid_string;
        // extract all the invalid ids

        $invalids = explode(" ", trim($invalid_string));

        // clean up the ids
        foreach ($invalids as & $i) {
            $i = ereg_replace("[^0-9]", "", $i);
        }

        //print_r($invalids);
        //return $invalids;

        // set the view variables

        $this->set(compact('invalids'));

        // and XML/JSON display
        $this->set('_serialize', array(
            'invalids'
        ));

        //echo WWW_ROOT;
        //$this->render('view', 'default', 'pl');

        //$this->render();


    }

    // list all the problem maps
    public function index() {

        // if user find all problem maps

        if ($this->Auth->user('admin') == 1) {
            $ProblemMaps = $this->ProblemMap->find('all', array('recursive' => 0));
        }
        else {

            // get all the problem maps belonging to the user
            $ProblemMaps = $this->ProblemMap->find('all', array(
                'conditions' => array(
                    'ProblemMap.user_id' => $this->Auth->user('id')
                )
            ));
        }

        // set them in a variable accessible in the view
        $this->set(compact('ProblemMaps'));

        // save them in a format accessible from JSON / XML
        $this->set('_serialize', array(
            'ProblemMaps'
        ));
    }
    public function view_list($id) {

        $this->log_entry($id, "ProblemMapsController, view_list, " . $id);

        // retrieve the problem map and set it to a variable accessible in the view
        $ProblemMap = $this->ProblemMap->findById($id);
        $this->set(compact('ProblemMap'));

        // this is for JSON and XML requests.
        $this->set('_serialize', array(
            'ProblemMap'
        ));
    }

    public function view_graph($id) {

        $this->log_entry($id, "ProblemMapsController, view_graph, " . $id);

        // retrieve the problem map and set it to a variable accessible in the view
        $ProblemMap = $this->ProblemMap->findById($id);
        $this->set(compact('ProblemMap'));

        // this is for JSON and XML requests.
        $this->set('_serialize', array(
            'ProblemMap'
        ));
    }
	
		
	public function view_predicate($id) {

        $this->log_entry($id, "ProblemMapsController, view_predicate, " . $id);

        // retrieve the problem map and set it to a variable accessible in the view
        $ProblemMap = $this->ProblemMap->findById($id);
        $this->set(compact('ProblemMap'));

        // this is for JSON and XML requests.
        $this->set('_serialize', array(
            'ProblemMap'
        ));
    }
	
	public function view_text($id) {

        $this->log_entry($id, "ProblemMapsController, view_predicate, " . $id);

        // retrieve the problem map and set it to a variable accessible in the view
        $ProblemMap = $this->ProblemMap->findById($id);
        $this->set(compact('ProblemMap'));

        // this is for JSON and XML requests.
        $this->set('_serialize', array(
            'ProblemMap'
        ));
    }

    public function view($id) {


        // retrieve the problem map and set it to a variable accessible in the view
        $ProblemMap = $this->ProblemMap->findById($id);
        $this->set(compact('ProblemMap'));

        // this is for JSON and XML requests.
        $this->set('_serialize', array(
            'ProblemMap'
        ));
    }
    public function view_log($id) {

        // retrieve the problem map log entries and set it to a variable accessible in the view
        $Log = $this->LogEntry->find('all', array(
            'conditions' => array(
                'LogEntry.problem_map_id =' => $id
            )
        ));
        $this->set(compact('Log'));

        // this is for JSON and XML requests.
        $this->set('_serialize', array(
            'Log'
        ));
    }
    public function add() {

        $error = false;

        // check if the data is being posted (submitted).

        if ($this->request->is('post')) {

            // get the logged in user id
            $this->request->data['ProblemMap']['user_id'] = $this->Auth->user('id');

            // start database transaction
            $this->ProblemMap->begin();

            // Save Problem Map

            if (!$this->ProblemMap->save($this->request->data)) {
                $error = true;
            }

            // handle transaction and message

            if ($error) {

                // rollback transaction
                $this->ProblemMap->rollback();
                $message = 'Error';

                // set message to be displayed to user via CakePHP flash
                $this->Session->setFlash('Unable to create problem map.');
            }
            else {

                // commit transaction
                $this->ProblemMap->commit();
                $message = 'Saved';

                // set message to be displayed to user via CakePHP flash
                $this->Session->setFlash('Your Problem Map has been created.');

                // redirec the user
                $this->redirect(array(
                    'action' => 'index'
                ));
            }

            // this is for JSON and XML requests.
            $this->set(compact("message"));
            $this->set('_serialize', array(
                'message'
            ));
        }
    }

    // edit the problem map
    public function edit($id) {

        $this->log_entry($id, "ProblemMapsController, edit, " . $id);

        // retrieve the current problem map if loading the form.
        $this->ProblemMap->id = $id;

        // check if get request (not submitting)

        if ($this->request->is('get')) {
            $this->request->data = $this->ProblemMap->read();
        }
        else {

            // here if the data has been posted. Save the new data and return result.

            if ($this->ProblemMap->save($this->request->data)) {
                $this->Session->setFlash('Your problem map has been updated.');
                $this->redirect(array(
                    'action' => 'index'
                ));
                $message = 'Saved';
            }
            else {
                $this->Session->setFlash('Unable to update your post.');
                $message = 'Error';
            }
        }

        // this is for JSON and XML requests.
        $this->set(compact("message"));
        $this->set('_serialize', array(
            'message'
        ));
    }

    // delete problem map
    public function delete($id) {

        $this->log_entry($id, "ProblemMapsController, delete, " . $id);

        // cannot delete with a get request (only POST).

        if ($this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        // delete the problem map and return the result.

        if ($this->ProblemMap->delete($id)) {

            // set message to display to user
            $this->Session->setFlash('The problem map with id: ' . $id . ' has been deleted.');

            // redirect user back to index
            $this->redirect(array(
                'action' => 'index'
            ));
            $message = 'Deleted';
        }
        else {
            $message = 'Error';
        }

        // this is for JSON and XML requests.
        $this->set(compact("message"));
        $this->set('_serialize', array(
            'message'
        ));
    }
}
