/* Global variables.. yuck I know... */
var subdirectory = '';
var EntityGraph;
var paper;
var Entities;
var Links;
var Decompositions;


/* Helper function */
function nonBlockingLoop(fun, iterations){
    fun();
    if (iterations > 0){
        setTimeout(function() { nonBlockingLoop(fun,iterations-1); }, 25); 
    }
    else if(iterations == -1){
        setTimeout(function() { nonBlockingLoop(fun,-1); }, 25); 
    }
}

/* trips whitespace off beginning and end of a string */
function trim (str) {
    return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}

/* The main show */
$(function(){
    var url = window.location.pathname 

    if (window.location.pathname.split('/')[1] != 'problem_maps'){
	subdirectory =  '/' + window.location.pathname.split('/')[1];
    }
    //console.log(subdirectory);
    var pMapId = url.substr(url.lastIndexOf('/') + 1);

/* Models */
var Entity = Backbone.Model.extend({
    url: function(){
        if (this.get('id'))
    return subdirectory + '/entities/' + this.get('id') + '.json';
        else
    return subdirectory + '/entities.json';
    },
    parse: function(response) {
        return response.Entity
    },
    toJSON: function() {
        var entity = _.clone(this.attributes);
        return {'Entity': entity};
    }
});

var Decomposition = Backbone.Model.extend({
    url: function(){
        if (this.get('id'))
    return subdirectory + '/decompositions/' + this.get('id') + '.json';
        else
    return subdirectory + '/decompositions.json';
    },
    parse: function(response) {
        return response.Decomposition
    },
    toJSON: function() {
        var decomposition = _.clone(this.attributes);
        return {'Decomposition': decomposition};
    }
});

var Link = Backbone.Model.extend({
    url: function(){
        if (this.get('id'))
    return subdirectory + '/links/' + this.get('id') + '.json';
        else
    return subdirectory + '/links.json';
    },
    parse: function(response) {
        return response.Link
    },
    toJSON: function() {
        var link = _.clone(this.attributes);
        return {'Link': link};
    }
});

/* Collections */ 
var EntityList = Backbone.Collection.extend({
    model: Entity,
    url: function() {
        return subdirectory + '/entities.json?problem_map_id=' + pMapId;
    },
    comparator: function(entity){
        // sort first by list_order then by id (order of creation)
        return parseInt(entity.get('list_order')) + 0.00001 * parseInt(entity.get('id'));
    }
});

var DecompositionList = Backbone.Collection.extend({
    model: Decomposition,
    url: function() {
        return subdirectory + '/decompositions.json?problem_map_id=' + pMapId;
    }
});

var LinkList = Backbone.Collection.extend({
    model: Link,
    url: function() {
        return subdirectory + '/links.json?problem_map_id=' + pMapId;
    }
});

/* Instantiations of the collections */
Entities = new EntityList();
Decompositions = new DecompositionList();
Links = new LinkList();

/* Views */
var EntityView = Backbone.View.extend({
});

var LinkView = Backbone.View.extend({
});

var DecompositionView = Backbone.View.extend({
});

var EntityGraphView = Backbone.View.extend({
});

//EntityGraph = new EntityGraphView();

function getChildrenEntities(entityType, id){
    //console.log("ent");
    var children = [];

    if (id == null){
        //console.log('beep');
        Entities.where({type: entityType, decomposition_id: null}).forEach(function(element){
                var data = {}
                data['name'] = element.get('name');
                data['children'] = getChildrenDecomps(entityType, element.id);
                children.push(data);
        });
    }
    else{
        Entities.where({type: entityType, decomposition_id: id}).forEach(function(element){
                var data = {}
                //data['name'] = "<div style='width:100px'>" + element.get('name') + "</div>";
		//data['name'] = "this is a test of a long entity\n I'm not sure if the new lines work\n We shall see.";
                data['name'] = element.get('name');
                data['children'] = getChildrenDecomps(entityType, element.id);
                children.push(data);
        });
    }

    return children;
}

function getChildrenDecomps(entityType, id){
    //console.log("dec");
    var children = [];

    Decompositions.where({entity_id: id}).forEach(function(element){
        var data = {}
        data['name'] = 'Decomp' + element.get('id');
        data['children'] = getChildrenEntities(entityType, element.get('id'));
        children.push(data);
    });

    return children;
}

function load_reingold_tilford_tree(type){
    var data = {};
    data['name'] = type + "s";
    data['children'] = getChildrenEntities(type);

    //console.log(data);

    // d3 business.
    var width = 350 + Entities.where({type: type}).length * 200;
    var height = Entities.where({type: type}).length * 20;

    var tree = d3.layout.tree()
        .size([height, width - 200]);

    var diagonal = d3.svg.diagonal()
        .projection(function(d) { return [d.y, d.x]; });

    var svg = d3.select("#tabs").append("svg")
        .attr("width", width)
        .attr("height", height)
      .append("g")
        .attr("transform", "translate(100,0)");


    // load the data
    var nodes = tree.nodes(data),
      links = tree.links(nodes);

    var link = svg.selectAll("path.link")
      .data(links)
    .enter().append("path")
      .attr("class", "link")
      .attr("d", diagonal);

    var node = svg.selectAll("g.node")
      .data(nodes)
    .enter().append("g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; })

    node.append("circle")
      .attr("r", 4.5);

    node.append("text")
      .attr("dx", function(d) { return d.children ? -8 : 8; })
      .attr("dy", 3)
      .attr("text-anchor", function(d) { return d.children ? "end" : "start"; })
      .text(function(d) { return d.name; });

    var test = svg.selectAll("g.node.text")
	.data(nodes)
 	.style("width", '100px');
}

/* Get data from server */
Links.fetch().done(function() {
    Entities.fetch().done(function(){
        Decompositions.fetch().done(function(){
        
            $('#tabs').append("<h2>Requirements</h2>");
            load_reingold_tilford_tree('requirement');            
            $('#tabs').append("<h2>Functions</h2>");
            load_reingold_tilford_tree('function');            
            $('#tabs').append("<h2>Artifacts</h2>");
            load_reingold_tilford_tree('artifact');            
            $('#tabs').append("<h2>Behaviors</h2>");
            load_reingold_tilford_tree('behavior');            
            $('#tabs').append("<h2>Issues</h2>");
            load_reingold_tilford_tree('issue');            

/*
            // use the stuff here?
            var space = Entities.length * 50;

            $('#tabs').append("<canvas id='canvas' width='" + (space) + "' height='" + (space) + "'></canvas");
            var graph = new Springy.Graph();
            Entities.each(function(entity){
                var node = new Springy.Node("e"+entity.get('id'), 
                                            {label: entity.get('name'),
                                             type: entity.get('type')});
                graph.addNode(node);
            });
            Links.each(function(link){
                graph.addEdges(["e" + link.get('from_entity_id'), "e" + link.get('to_entity_id'), {label: link.get('type')}]);
            });

            Decompositions.each(function(decomposition){
                var node = new Springy.Node("d" + decomposition.get('id'),
                                            {label: "  ",
                                             type: "decomposition"});
                graph.addNode(node);
                graph.addEdges(["e" + decomposition.get('entity_id'),
                               "d" + decomposition.get('id'), {label: 'has child'}]);


                _.each(Entities.where({'decomposition_id': decomposition.get('id')}),
                function(entity){
                    graph.addEdges(['d' + decomposition.get('id'), 
                                    'e' + entity.get('id'),
                                    {label: 'has child'}]);
                });

            });

            var springy = $('#canvas').springy({
                graph: graph
            });
*/

        });    
    });
});

function updateSearchIcon(e){
    if($(e.target).val().length > 0){
        $(e.target).parent().find('i')
            .removeClass('icon-search')
            .addClass('icon-remove-sign')
            .unbind()
            .on('click', function(e){
                $(e.target).parent().parent().find('input').val('');
                $(e.target).parent().parent().find('input').change();
                $('.active').removeClass('active');
            });
    }
    else{
        $(e.target).parent().find('i')
            .unbind()
            .removeClass('icon-remove-sign')
            .addClass('icon-search');
    }

    var entity = Entities.findWhere({name: $(e.target).val()});

    if (entity){
        id = entity.get('id');
        selector = $('li[entity-id="' + id + '"]');
        while (selector.length == 0){
            var entity = Entities.findWhere({id: id});
            if (entity.get('decomposition_id') == null){
                 break;
            }
            var decomp = Decompositions.findWhere({id: entity.get('decomposition_id')})
            if (!decomp){
                break;
            }
            id = decomp.get('entity_id');
            selector = $('li[entity-id="' + id + '"]');
        }
        $('.active').removeClass('active');
        selector.addClass('active');
    }

}

$('.search-query').on('keyup', function(e){
    updateSearchIcon(e);
});
$('.search-query').on('change', function(e){
    updateSearchIcon(e);
});

$('.search-query').typeahead({
    source: function(query, process){
        return Entities.pluck('name');
    }
});


});
