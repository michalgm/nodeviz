var GraphRaster = Class.create(GraphImage, {
	initialize: function($super, NodeViz) {
		$super(NodeViz);
		if (! $('highlight')) { 
			$('graphs').insert({ top: new Element('div', {'id': 'highlight'}) });
		}
		$('highlight').innerHTML += this.highlightImageHTML;
	},
	reset: function($super) {
		$super();
		if ($('G')) { 
			var data = this.NodeViz.data;
			$('G').descendants().each(function(a) { 
				if (data.edges[a.id] || data.nodes[a.id]) { 
					$(a).stopObserving();
				}
			});
		}
		$('highlight').stopObserving();
		$('highlightimg').stopObserving();
	},
	render: function($super, responseData) { 
		$super();
		var image = responseData.image;
		var overlay = responseData.overlay;
		var map = " usemap='#G'";
		$('images').update("<img id='image' "+map+" border='0' src='"+image+"' />"+overlay);
		$('G').descendants().each(function(a) { 
			if (this.NodeViz.data.edges[a.id]) { 
				var edge = this.NodeViz.data.edges[a.id];
				Event.observe($(a), 'mouseout', this.hideTooltip.bind(this)); 
				Event.observe($(a), 'mousemove', this.mousemove.bind(this));
				if (edge.onMouseover != '') { Event.observe($(a), 'mouseover', function(e) { eval(edge.onMouseover); }.bind(this)); }
				if (edge.onClick != '') { Event.observe($(a), 'click', function(e) { eval(edge.onClick); }.bind(this)); }
			} else if (this.NodeViz.data.nodes[a.id]) { 
				var node = this.NodeViz.data.nodes[a.id];
				if (node.onMouseover != '') { Event.observe($(a), 'mouseover', function(e) { eval(node.onMouseover); }.bind(this)); }
				if (node.onClick != '') { Event.observe($(a), 'click', function(e) { eval(node.onClick); }.bind(this)); }
				//Event.observe($(a), 'mouseout', function(e) { this.NodeViz.unhighlightNode(a.id); }.bind(this)); 
				var coords = $(a).getAttribute('coords').split(',');
				if ($(a).getAttribute('shape') == 'circle') {
					node['width'] = coords[2]*2;
					node['height'] = coords[2]*2;
					node['posx'] = coords[0] - (coords[2]);
					node['posy'] = coords[1] - (coords[2]);
					
				} else {
					node['width'] = (coords[2] - coords[0]);
					node['height'] = (coords[3] - coords[1]);
					node['posx'] = coords[0];
					node['posy'] = coords[1];
				}
			}
		}, this);
		this.setupListeners();
	},
	setupListeners: function($super) {
		$super();
		Event.observe($('highlight'), 'mousemove', this.mousemove.bind(this));
		Event.observe($('highlight'), 'click', this.NodeViz.selectNode.bind(this.NodeViz));
		//Event.observe($('highlightimg'), 'click', this.NodeViz.selectNode.bind(this.NodeViz));
		//Event.observe($('highlight'), 'mouseover', this.highlightNode);
		Event.observe($('highlightimg'), 'mouseout', this.NodeViz.unhighlightNode.bind(this.NodeViz));
		Event.observe($('highlight'), 'mouseout', this.NodeViz.unhighlightNode.bind(this.NodeViz));
	},
	highlightNode: function($super, id, text, noshowtooltip) {
		$super(id, text, noshowtooltip);
		var node = this.NodeViz.data.nodes[id];
		$('highlight').style.width = parseFloat(node['width']) +2 +'px';
		$('highlight').style.height = parseFloat(node['height']) +2 +'px';
		$('highlight').style.top = parseFloat(node['posy']) -1 + this.NodeViz.offsetY + 'px';
		$('highlight').style.left = parseFloat(node['posx']) -1 + this.NodeViz.offsetX + 'px';
		$('highlight').style.visibility = 'visible';
		if (node['shape'] != 'circle') { 
			$('highlight').addClassName('selected');
			$('highlightimg').style.visibility = 'hidden';
		} else {
			$('highlight').removeAttribute('class');
			$('highlightimg').style.visibility = 'visible';
		}
	},
	unhighlightNode: function($super, id) {
		$super(id);
		$('highlight').style.visibility = 'hidden';
		$('highlightimg').style.visibility = 'hidden';
	},

	highlightImageHTML: "\
		<div id='highlight'><img id='highlightimg' alt='' src='images/highlight.gif' /></div>\
	",

});
