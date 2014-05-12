<?php $this->Html->css('view_graphNew', null, array('inline' => false)); ?>
<?php $this->Html->script('underscore.min', false); ?>
<?php $this->Html->script('backbone.min', false); ?>
<?php $this->Html->script('backbone-relational', false); ?>
<?php $this->Html->script('jquery-sortable.min', false); ?>
<?php $this->Html->script('bootstrap-contextmenu', false); ?>
<?php $this->Html->script('http://d3js.org/d3.v3.min.js', false); ?>
<style>

.node {
  font: 300 11px "Helvetica Neue", Helvetica, Arial, sans-serif;
  fill: #bbb;
}

.node:hover {
  fill: #000;
}

.link {
  stroke: steelblue;
  stroke-opacity: .4;
  fill: none;
  pointer-events: none;
}

.thelink {
  stroke: steelblue;
  stroke-opacity: .4;
  fill: none;
  pointer-events: none;
}

.node:hover,
.node--source,
.node--target {
  font-weight: 700;
}

.node--source {
  fill: #2ca02c;
}

.node--target {
  fill: #d62728;
}

.link--source,
.link--target {
  stroke-opacity: 1;
  stroke-width: 2px;
}

.link--source {
  stroke: #d62728;
}

.link--target {
  stroke: #d62728;
}

.thelink--source,
.thelink--target {
  stroke-opacity: 1;
  stroke-width: 2px;
}

.thelink--source {
  stroke: #CDCD00;
}

.thelink--target {
  stroke: #CDCD00;
}




</style>
<body>
<script src="http://d3js.org/d3.v3.min.js"></script>
<script>
//d62728    2ca02c
var diameter = 960,
    radius = diameter / 2,
    innerRadius = radius - 120;

var cluster = d3.layout.cluster()
    .size([360, innerRadius])
    .sort(null)
    .value(function(d) { return d.size; });

var bundle = d3.layout.bundle();

var line = d3.svg.line.radial()
    .interpolate("bundle")
    .tension(.85)
    .radius(function(d) { return d.y; })
    .angle(function(d) { return d.x / 180 * Math.PI; });

var svg = d3.select("body").append("svg")
    .attr("width", diameter)
    .attr("height", diameter)
    .attr("style", "top: 150px; left: 200px; width: 1280px; height: 1280px; position: absolute;")
    //.attr("style", "text-align:center;")
  	.append("g")
    .attr("transform", "translate(" + radius + "," + radius + ")");

var link = svg.append("g").selectAll(".link"),
    node = svg.append("g").selectAll(".node"),
    thelink = svg.append("g").selectAll(".thelink");

d3.json("../../problemMapStructure.json", function(error, classes) {
  //alert(1);
  //alert(classes);
  var nodes = cluster.nodes(packageHierarchy(classes)),
      links = packageImports(nodes),
      thelinks = packageImports1(nodes);
  //alert(nodes);
  //alert(links);
  link = link
      .data(bundle(links))
      .enter().append("path")
      .each(function(d) { d.source = d[0], d.target = d[d.length - 1]; })
      .attr("class", "link")
      .attr("d", line);

  thelink = thelink
      .data(bundle(thelinks))
      .enter().append("path")
      .each(function(d) { d.source = d[0], d.target = d[d.length - 1]; })
      .attr("class", "thelink")
      .attr("d", line);	
      
  node = node
      .data(nodes.filter(function(n) { return !n.children; }))
    .enter().append("text")
      .attr("class", "node")
      .attr("dx", function(d) { return d.x < 180 ? 8 : -8; })
      .attr("dy", ".31em")
      .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")" + (d.x < 180 ? "" : "rotate(180)"); })
      .style("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
      .text(function(d) { return d.key; })
      .on("mouseover", mouseovered)
      .on("mouseout", mouseouted);
});

function mouseovered(d) {
  node
      .each(function(n) { n.target = n.source = false; });

  link
      .classed("link--target", function(l) { if (l.target === d) return l.source.source = true; })
      .classed("link--source", function(l) { if (l.source === d) return l.target.target = true; })
      .filter(function(l) { return l.target === d || l.source === d; })
      .each(function() { this.parentNode.appendChild(this); });
      
  thelink
      .classed("thelink--target", function(l) { if (l.target === d) return l.source.source = true; })
      .classed("thelink--source", function(l) { if (l.source === d) return l.target.target = true; })
      .filter(function(l) { return l.target === d || l.source === d; })
      .each(function() { this.parentNode.appendChild(this); });

  node
      .classed("node--target", function(n) { return n.target; })
      .classed("node--source", function(n) { return n.source; });
}

function mouseouted(d) {
  link
      .classed("link--target", false)
      .classed("link--source", false);
  
  thelink
      .classed("thelink--target", false)
      .classed("thelink--source", false);

  node
      .classed("node--target", false)
      .classed("node--source", false);
}

d3.select(self.frameElement).style("height", diameter + "px");

// Lazily construct the package hierarchy from class names.
function packageHierarchy(classes) {
  var map = {};

  function find(name, data) {
    var node = map[name], i;
    if (!node) {
      node = map[name] = data || {name: name, children: []};
      if (name.length) {
        node.parent = find(name.substring(0, i = name.lastIndexOf(".")));
        node.parent.children.push(node);
        node.key = name.substring(i + 1);
      }
    }
    return node;
  }

  classes.forEach(function(d) {
    find(d.name, d);
  });
  //alert(map);
  return map[""];
}

// Return a list of imports for the given array of nodes.
function packageImports1(nodes) {
  var map = {},
      thelinks = [];

  // Compute a map from name to node.
  nodes.forEach(function(d) {
    map[d.name] = d;
  });

  // For each import, construct a link from the source to target node.
  nodes.forEach(function(d) {
    if (d.thelinks) d.thelinks.forEach(function(i) {
      thelinks.push({source: map[d.name], target: map[i]});
    });
  });

  return thelinks;
}

function packageImports(nodes) {
  var map = {},
      children1 = [];

  // Compute a map from name to node.
  nodes.forEach(function(d) {
    map[d.name] = d;
  });

  // For each import, construct a link from the source to target node.
  nodes.forEach(function(d) {
    if (d.children1) d.children1.forEach(function(i) {
      children1.push({source: map[d.name], target: map[i]});
    });
  });

  return children1;
}

</script>

<script type="text/template" id="entity-template">
<% if (num_decomps > 0 && Entity.current_decomposition == null) { %>
    <i class="icon icon-folder-close pull-left"></i>
<% } else if (num_decomps > 0) { %>
    <i class="icon icon-folder-open pull-left"></i>
<% } else { %>
    <i class="icon icon-file pull-left"></i>
<% } %>
<% if (num_decomps > 1) { %>
    <span class='sup pull-left'><%= num_decomps %></span>
<% } else { %>
    <span class='sup pull-left'></span>
<% } %>
<div class='name pull-left editable' contenteditable=false>
    <%= Entity.name %>
</div>
<a class='destroy pull-right' href="#"><i class="icon-trash"></i></a>
<div class="clear"></div>
</script>

<script type="text/template" id="entity-tab-template">
<div class="row-fluid">
    <h2>
    <%= title %>
    <a href="#" id="<%= type %>-tooltip"><i class="icon-question-sign"></i></a>
    </h2>
</div>
<div class="row-fluid">
    <div class="input-append entity-dialog">
        <input id='new-<%= type %>' type='text' class='entity-input' 
        placeholder='New <%= type %>'></input>
        <button type='submit' class='btn-primary entity-input'>
            <i class="icon-plus"></i>
        </button>
    </div>
</div>
<hr>
<div class="row-fluid">
    <ul id='<%= type %>' class='entity-list'>
    </ul>
</div>
</script>

<div class="row-fluid">
    <div class="span10 offset1 page-header">
        <h1><?php echo $ProblemMap['ProblemMap']['name']; ?>
            <small>(<?php echo $this->Html->link("view as list", array(
                'controller' => 'problem_maps',
                'action' => 'view_list',
                $ProblemMap['ProblemMap']['id']
            )); ?>)</small>
            <br\>
            <small>
            	<font color="red">Red: Relations; </font>
            	<font color="#CDCD00">Yellow: Links</font>
            </small>
            <!--
            <div class="navbar-search pull-right">
                <div class="input-append">
                    <input type="text" class="search-query" placeholder="Search entities">
                    <span class="forsearch"><i class='icon-search'></i></span>
                </div>
            </div>
            -->
        </h1>
    </div>
</div>
<div id='tabs' class="row-fluid">
</div>

<div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"
        aria-hidden="true">x</button>
    <h3 id="myModalLabel">Which decomposition?</h3>
  </div>
  <div class="modal-body temp-decomps">
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
  </div>
</div>

<div id="context-menu">
    <ul class="dropdown-menu" role="menu">
    </ul>
</div>
