<?php
App::uses('File', 'Utility');

// Controller/ProblemMapsController.php
class ProblemMapRank {
	public $id;
	public $decomposition_id;
	public $name;
	public $type;
	public $current_decomposition;
	public $problem_map_id;
	public $thelinks=array();
	public $children1=array();
}


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
            'view_graphNew',
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
	
	//Create a new graph view by Zongkun
	public function view_graphNew($id) {
		//For nodes and children
		$array = array();
		$return_arr = array();
		$conn=mysql_connect("localhost","root","");
		$select=mysql_select_db("problemFormulator",$conn);
		//Requirements----------------
		$fetch1 = mysql_query("SELECT * FROM `entities` where problem_map_id = $id and type = 'requirement'"); 
		while ($row = mysql_fetch_array($fetch1, MYSQL_ASSOC)) {
			$e = new ProblemMapRank;
			$e->id = $row['id'];
			$e->decomposition_id = $row['decomposition_id'];
			$e->name = $row['name'];
			$e->type = $row['type'];
			$e->current_decomposition = $row['current_decomposition'];
			$e->problem_map_id = $row['problem_map_id'];
			$array[] = $e;
		}
		//Functions----------------
		$fetch2 = mysql_query("SELECT * FROM `entities` where problem_map_id = $id and type = 'function'"); 
		while ($row = mysql_fetch_array($fetch2, MYSQL_ASSOC)) {
			$e = new ProblemMapRank;
			$e->id = $row['id'];
			$e->decomposition_id = $row['decomposition_id'];
			$e->name = $row['name'];
			$e->type = $row['type'];
			$e->current_decomposition = $row['current_decomposition'];
			$e->problem_map_id = $row['problem_map_id'];
			$array[] = $e;
		}
		//Artifacts----------------
		$fetch3 = mysql_query("SELECT * FROM `entities` where problem_map_id = $id and type = 'artifact'"); 
		while ($row = mysql_fetch_array($fetch3, MYSQL_ASSOC)) {
			$e = new ProblemMapRank;
			$e->id = $row['id'];
			$e->decomposition_id = $row['decomposition_id'];
			$e->name = $row['name'];
			$e->type = $row['type'];
			$e->current_decomposition = $row['current_decomposition'];
			$e->problem_map_id = $row['problem_map_id'];
			$array[] = $e;
		}
		//Behaviors----------------
		$fetch4 = mysql_query("SELECT * FROM `entities` where problem_map_id = $id and type = 'behavior'"); 
		while ($row = mysql_fetch_array($fetch4, MYSQL_ASSOC)) {
			$e = new ProblemMapRank;
			$e->id = $row['id'];
			$e->decomposition_id = $row['decomposition_id'];
			$e->name = $row['name'];
			$e->type = $row['type'];
			$e->current_decomposition = $row['current_decomposition'];
			$e->problem_map_id = $row['problem_map_id'];
			$array[] = $e;
		}
		//Issues----------------
		$fetch5 = mysql_query("SELECT * FROM `entities` where problem_map_id = $id and type = 'issue'"); 
		while ($row = mysql_fetch_array($fetch5, MYSQL_ASSOC)) {
			$e = new ProblemMapRank;
			$e->id = $row['id'];
			$e->decomposition_id = $row['decomposition_id'];
			$e->name = $row['name'];
			$e->type = $row['type'];
			$e->current_decomposition = $row['current_decomposition'];
			$e->problem_map_id = $row['problem_map_id'];
			$array[] = $e;
		}
		//print_r($array);
		//echo json_encode($array);
		foreach ($array as $e) {
    		//unset($array[$i]);
    		
    		foreach ($array as $tmp){
    			if($tmp->decomposition_id!=null){
    				if($e->current_decomposition!=null){
		    			if($tmp->decomposition_id == $e->current_decomposition ){
							$e->children1[] = $tmp->name;
		    			}
					}
    			}
    		//echo json_encode($tmp->decomposition_id);
    		//echo json_encode($e->current_decomposition);
    		//echo json_encode($e->children);
    		}
    		// print_r(" ;name up: ");
			// echo json_encode($e->name);
			// print_r("children: ");
			// echo json_encode($e->children);
			//echo json_encode($e);
		}
		//For links
		$linkFetch = mysql_query("SELECT * FROM `links` where problem_map_id = $id"); 
		while ($row = mysql_fetch_array($linkFetch, MYSQL_ASSOC)) {
			//echo json_encode($row['from_entity_id']);
			foreach ($array as $e) {
				if($row['from_entity_id']==$e->id){
					//$e->thelinks[] = $row['to_entity_id'];
					foreach ($array as $tmp) {
						if($tmp->id == $row['to_entity_id']){
							$e->thelinks[] = $tmp->name;
						}
					}
				}
			}
		}
		$outPutJson =  json_encode($array);
		file_put_contents('problemMapStructure.json',$outPutJson);
		
		
         $this->log_entry($id, "ProblemMapsController, view_graph_2, " . $id);

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
		$this->Session->setFlash('.....');
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
