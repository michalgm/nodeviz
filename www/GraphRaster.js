var GraphRaster = Class.create(GraphImage, {
	initialize: function($super, framework) {
		$super(framework);
		$(this.graphdiv).innerHTML += this.highlightImageHTML;
	},
	reset: function() {
	},
	renderGraph: function($super, image, overlay) { 
		$super();
		var map = " usemap='#G'";
		$('images').update("<img id='img0' "+map+" border='0' src='"+image+"' />"+overlay);
		$('G').descendants().each(function(a) { 
			if (this.Framework.data.edges[a.id]) { 
				Event.observe($(a), 'mouseout', this.hideTooltip.bind(this)); 
				Event.observe($(a), 'mousemove', this.mousemove.bind(this));
			} else if (this.Framework.data.nodes[a.id]) { 
				if (this.Framework.data.nodes[a.id].onMouseover != '') { Event.observe($(a), 'mouseover', function(e) { eval(this.Framework.data.nodes[a.id].onMouseover); }.bind(this)); }
				if (this.Framework.data.nodes[a.id].onClick != '') { Event.observe($(a), 'click', function(e) { eval(this.Framework.data.nodes[a.id].onClick); }.bind(this)); }
				//Event.observe($(a), 'mouseout', function(e) { this.Framework.unhighlightNode(a.id); }.bind(this)); 
			}
		}, this);
		this.setupListeners();
	},
	setupListeners: function($super) {
		$super();
		Event.observe($('highlight'), 'mousemove', this.mousemove.bind(this));
		Event.observe($('highlight'), 'click', this.Framework.selectNode.bind(this.Framework));
		//Event.observe($('highlightimg'), 'click', this.Framework.selectNode.bind(this.Framework));
		//Event.observe($('highlight'), 'mouseover', this.highlightNode);
		Event.observe($('highlightimg'), 'mouseout', this.Framework.unhighlightNode.bind(this.Framework));
		Event.observe($('highlight'), 'mouseout', this.Framework.unhighlightNode.bind(this.Framework));
	},
	highlightNode: function($super, id, text, noshowtooltip) {
		$super(id, text, noshowtooltip);
		var node = this.Framework.data.nodes[id];
		$('highlight').style.width = parseFloat(node['width']) +2 +'px';
		$('highlight').style.height = parseFloat(node['height']) +2 +'px';
		$('highlight').style.top = parseFloat(node['posy']) -1 + this.Framework.offsetY + 'px';
		$('highlight').style.left = parseFloat(node['posx']) -1 + this.Framework.offsetX + 'px';
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
		<div id='highlight' style='position: absolute; z-index: 5; visibility: hidden; filter:alpha(opacity=50); opacity: .50; cursor: pointer; top: 0px; left: 0px;'><img id='highlightimg' alt='' style='visibility: hidden; width: 100%; height: 100%;' src='images/highlight.gif' /></div>\
	",

});
