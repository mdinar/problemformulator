<!-- File: /app/View/ProblemMaps/index.ctp -->
<?php
$this->Html->css('index-pmap', null, array('inline' => false));
?>

<div class="page-header text-center">
    <h1>Available Problem Maps</h1>
    <p><?php echo $this->Html->link('Add New Problem Map', array(
        'action' => 'add'
       )); ?>
    </p>
</div>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Id</th>
            <th>Owner</th>
            <th>Name</th>
            <th>Description</th>
            <th>Actions</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>

        <!-- Here is where we loop through our $ProblemMaps array, printing out Problem_map info -->

        <?php foreach ($ProblemMaps as $problem_map): ?>
        <tr>
            <td><?php echo $problem_map['ProblemMap']['id']; ?></td>
            <td><?php echo $problem_map['User']['firstname'] . ' ' . $problem_map['User']['lastname']; ?>
            <td>
                <?php echo $this->Html->link($problem_map['ProblemMap']['name'], array(
        'controller' => 'problem_maps',
        'action' => 'view_list',
        $problem_map['ProblemMap']['id']
    )); ?>
            </td>
            <td><?php echo $problem_map['ProblemMap']['description']; ?></td>
            <td>
            <?php echo $this->Html->link('Edit', array(
        'action' => 'edit',
        $problem_map['ProblemMap']['id']
    )); ?>
<!---
            <?php echo $this->Form->postLink('Delete', array(
        'action' => 'delete',
        $problem_map['ProblemMap']['id']
    ) , array(
        'confirm' => 'Are you sure?'
    ));
?>
--->
            </td>
            <td><?php echo $problem_map['ProblemMap']['created']; ?></td>
        </tr>
        <?php
endforeach; ?>
    </tbody>

</table>
