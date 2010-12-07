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
			if (a.id.search('_') != -1) { 
				Event.observe($(a), 'mouseout', hideTooltip, 1); 
				Event.observe($(a), 'mousemove', mousemove);
			}
			if (framework.nodes[a.id]) { 
				if (framework.nodes[a.id].onMouseover != '') { Event.observe($(a), 'mouseover', function(eventObject) { eval(framework.nodes[a.id].onMouseover); }); }
				if (framework.nodes[a.id].onClick != '') { Event.observe($(a), 'click', function(eventObject) { eval(framework.nodes[a.id].onClick); }); }
			}
		});
		this.setupListeners();
	},
	setupListeners: function($super) {
		$super();
		Event.observe($('highlight'), 'mousemove', this.mousemove.bind(this));
		//Event.observe($('highlight'), 'mouseover', this.highlightNode);
		Event.observe($('highlightimg'), 'mouseout', this.hideTooltip.bind(this));
		Event.observe($('highlight'), 'mouseout', this.hideTooltip.bind(this));
	},
	highlightNode: function($super, id, text, noshowtooltip) {
		$super(id, text, noshowtooltip);
		var node = this.Framework.data.nodes[id];
		$('highlight').style.width = parseFloat(node['width']) +2 +'px';
		$('highlight').style.height = parseFloat(node['height']) +2 +'px';
		$('highlight').style.top = parseFloat(node['posy']) -1 + offsetY + 'px';
		$('highlight').style.left = parseFloat(node['posx']) -1 + offsetX + 'px';
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
