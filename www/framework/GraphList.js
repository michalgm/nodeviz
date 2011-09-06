
var GraphList = Class.create();

GraphList.prototype = {

	initialize: function(framework) {
		this.Framework = framework;
		this.listdiv = this.Framework.listdiv;
	},
	reset: function() { 
		var data = this.Framework.data;
		var parent = '#'+this.listdiv;
		//TODO - there may be a slightly more efficient way of finding these nodes - gm - 12/28/10
		$$(parent+' li', parent+' div').each(function(e) { 
			e.stopObserving();
		});
		$(this.listdiv).update('');
	},
	appendOptions: function() {

	},
	render: function(responseData) {
		var data = this.Framework.data;
		this.nodeLists = new Hash();
		$(this.listdiv).insert({ top: new Element('ul', {'id': 'list_menu'}) });
		$H(data.nodetypes).values().each( function(nodetype) {
			this.nodeLists[nodetype] = new Hash();
			$('list_menu').insert({ bottom: new Element('li', {'id': nodetype+'_menu'}).update(nodetype)});
			$(this.listdiv).insert({ bottom: new Element('div', {'id': nodetype+'_list_container', 'class': 'nodelist_container'}) });
			$(nodetype+'_list_container').insert({top: new Element('div', {'id': nodetype+'_list_header', 'class': 'nodelist_header'}).update('<span class="node_type_label">'+nodetype+' Nodes</span>')});
			$(nodetype+'_list_container').insert({ bottom: new Element('ul', {'id': nodetype+'_list', 'class': 'nodelist'}) });

			var search = ' <label for="'+nodetype+'_search">Search</label> <input class="node_search" id="'+nodetype+'_search" autocomplete="off" size="20" type="text" value="" /> <div class="autocomplete node_search_list" id="'+nodetype+'_search_list" style="display:none"></div>';
			$(nodetype+'_list_header').insert({bottom: new Element('div', {'id': nodetype+'_search_container', 'class': 'node_search_container'}).update(search)});

			Event.observe($(nodetype+'_menu'), 'click', function(e) { this.displayList(nodetype); }.bind(this));
			$H(data.nodetypesindex[nodetype]).values().each( function(nodeid) {
				var node = data.nodes[nodeid];
				if (node['Name']) { 
					this.nodeLists[nodetype].set(node['Name'], nodeid);
				}
				$(nodetype+'_list').insert({ bottom: new Element('li', {'id': 'list_'+nodeid}) });
				$('list_'+nodeid).update(this.listNodeEntry(node));
				$('list_'+nodeid).insert({ bottom: new Element('div', {'id': nodeid+'_sublists', 'class': 'sublists_container'}) });
				Event.observe($('list_'+nodeid), 'mouseover', function(e) { this.highlightNode(nodeid, 1); }.bind(this.Framework));
				Event.observe($('list_'+nodeid), 'mouseout', function(e) { this.unhighlightNode(nodeid); }.bind(this.Framework));
				Event.observe($('list_'+nodeid), 'click', function(e) { this.selectNode(nodeid); }.bind(this.Framework));
				Event.observe($(nodeid+'_sublists'), 'click', function(e) { e.stop(); }.bind(this.Framework));
				Event.observe($(nodeid+'_sublists'), 'mouseover', function(e) { e.stop(); }.bind(this.Framework));
				$H(data.edgetypes).keys().each( function(edgetype) {
					this.setupSubLists(node, edgetype, 'from'); 
					this.setupSubLists(node, edgetype, 'to'); 
				}, this);
			}, this);
			if (this.nodeLists[nodetype].keys()[0]) { 
				new Autocompleter.Local(nodetype+'_search', nodetype+'_search_list', this.nodeLists[nodetype].keys(), {
					'fullSearch': true, 
					afterUpdateElement: function (t, l) {
						if (t.value && this.nodeLists[this.Framework.current.nodetype].get(t.value)) { 
							this.Framework.selectNode(this.nodeLists[this.Framework.current.nodetype].get(t.value));
						}
						t.value = '';
					}.bind(this)
				}, this);
			}
		}, this);
		this.displayList(data.nodetypes[0]);
	},
	displayList: function(nodetype) { 
		oldnodetype = this.Framework.current['nodetype'];
		if(oldnodetype != '') { 
			$(oldnodetype+'_list_container').removeClassName('selected');
			$(oldnodetype+'_menu').removeClassName('selected');
		}
		$(nodetype+'_list_container').addClassName('selected');
		$(nodetype+'_menu').addClassName('selected');
		this.Framework.current['nodetype'] = nodetype;
	},
	setupSubLists: function(node, edgetype, direction) { 
		var data = this.Framework.data;
		var dirindex = direction == 'from' ? 0 : 1; 
		var otherdir = direction == 'from' ? 'to' : 'from';
		var nodeid = node.id;
		var nodetype = node.type;
		var nodediv = 'list_'+nodeid;
		if (data.edgetypes[edgetype][dirindex] == nodetype) { 
			var nodes = [];
			$H(data.edgetypesindex[edgetype]).values().each( function(edgeid) { 
				var edge = data.edges[edgeid];
				if (edge[direction+'Id'] == nodeid) { 
					var snode = data.nodes[data.edges[edgeid][otherdir+'Id']];
					nodes.push(snode);
				}
			}, this);
			if (nodes.size() >= 1) {
				var sublistdiv = nodediv+'_'+edgetype+'_'+direction;
				var classes = edgetype+'_list '+direction+'_list';
				$(nodeid+'_sublists').insert({ bottom: new Element('ul', {'id': sublistdiv,'class':classes}) });
				
				$(sublistdiv).insert({ bottom: new Element('span', {'id': sublistdiv+'_header','class': 'node_type_label'}).update(edgetype+' '+direction+' Nodes') });
				nodes.each(function(snode) {
					$(sublistdiv).insert({ bottom: new Element('li', {'id': sublistdiv+'_'+snode.id}) });
					$(sublistdiv+'_'+snode.id).update(this.listSubNodeEntry(snode, node, edgetype, direction));
					Event.observe($(sublistdiv+'_'+snode.id), 'mouseover', function(e) { this.highlightNode(snode.id, 1); }.bind(this.Framework));
					Event.observe($(sublistdiv+'_'+snode.id), 'mouseout', function(e) { this.unhighlightNode(snode.id); }.bind(this.Framework));
				}, this);
			}	
		}
	},
	listNodeEntry: function(node) {
		var label;
		var content = "";
		if (node.Name) { 
			label = node.Name;
		} else { 
			label = node.id;
		}
		return "<span>"+label+"</span>";
	},
	listSubNodeEntry: function(node, parentNode, edgetype, direction) { 
		var label;
		var node_class = direction == 'to' ? 'to_node_item' : 'from_node_item'; 
		if (node.Name) { 
			label = node.Name;
		} else { 
			label = node.id;
		}
		var link = "<span class='link_icon' onclick='gf.selectNode(\""+node.id+"\");'>&#187;</span>";
		return link+"<span  onclick='gf.selectNode(\""+node.id+"\");' class='"+node_class+"'>"+label+"</span>";
	},
	highlightNode: function (id) { 
		var networkNode = this.Framework.data.nodes[this.Framework.current.network];
		var highlightNode = this.Framework.data.nodes[id];
		if(networkNode && networkNode.type != highlightNode.type && networkNode.type == this.Framework.current.nodetype) { 
			var other_id = this.Framework.current.network;
			$H(highlightNode.relatedNodes[other_id]).values().each( function(edgeid) { 
				var edge = this.Framework.data.edges[edgeid];
				var type = edge.type;
				var dir = edge.toId == id ? 'from' : 'to';
				var subnodeid = 'list_'+other_id+'_'+type+'_'+dir+'_'+id;
				$(subnodeid).addClassName('highlight');
			}, this);
		} else {
			$('list_'+id).addClassName('highlight');
		}
	},
	unhighlightNode: function (id) { 
		var networkNode = this.Framework.data.nodes[this.Framework.current.network];
		var highlightNode = this.Framework.data.nodes[id];
		if(networkNode && networkNode.type != highlightNode.type && networkNode.type == this.Framework.current.nodetype) { 
			var other_id = this.Framework.current.network;
			$H(highlightNode.relatedNodes[other_id]).values().each( function(edgeid) { 
				var edge = this.Framework.data.edges[edgeid];
				var type = edge.type;
				var dir = edge.toId == id ? 'from' : 'to';
				var subnodeid = 'list_'+other_id+'_'+type+'_'+dir+'_'+id;
				$(subnodeid).removeClassName('highlight');
			}, this);
		} else { 
			$('list_'+id).removeClassName('highlight');
		}
	},
	selectNode: function(id) { 
		this.displayList(this.Framework.data.nodes[id].type);
		var elem = $('list_'+id);
		elem.addClassName('selected');
		$(id+'_sublists').setStyle({'display': 'block'});
		if (this.Framework.scrollList) { 
			elem.parentNode.scrollTop = elem.offsetTop;
		}
	},
	unselectNode: function(id, fade) { 
		$('list_'+id).removeClassName('selected');
		$(id+'_sublists').setStyle({'display': 'none'});
	}
};
