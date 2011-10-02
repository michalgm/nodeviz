
var GraphImage = Class.create();

GraphImage.prototype = {
	initialize: function(NodeViz) {
		this.NodeViz = NodeViz;
		this.graphdiv = this.NodeViz.graphdiv;
		if (! $('tooltip')) { 
			$(document.body).insert({ top: new Element('div', {'id': 'tooltip'}) });
		}
		$(this.graphdiv).innerHTML += "<div id='images'></div>";
		$(this.graphdiv).innerHTML += "<div id='imagescreen' style='display:none;'></div>";
		this.graphDimensions = $(this.graphdiv).getDimensions();
	},
	reset: function() {
		Event.stopObserving($('image'));
		this.graphDimensions = $(this.graphdiv).getDimensions();
	},
	render: function(responseData) {
	},
	appendOptions: function() {
		this.NodeViz.params.useSVG = this.NodeViz.useSVG;
		this.NodeViz.params.graphWidth = this.graphDimensions.width;
		this.NodeViz.params.graphHeight = this.graphDimensions.height;
	},
	//Catches mousemove events
	mousemove: function(e) {
		if($('tooltip').style.visibility == 'visible') { 
			var mousepos = { 'x': Event.pointerX(e), 'y': Event.pointerY(e) };
			$('tooltip').setStyle({'top': (mousepos['y']- this.NodeViz.tooltipOffsetY - $('tooltip').getHeight()) + 'px', 'left': (mousepos['x']  - this.NodeViz.tooltipOffsetX) + 'px'});
		}
	},
	setupListeners: function() { 
		Event.observe($('image'), 'mousemove', this.mousemove.bind(this));
		Event.observe($('tooltip'), 'mousemove', this.mousemove.bind(this))
		Event.observe($('tooltip'), 'mouseout', this.hideTooltip.bind(this));
		Event.observe(window, 'resize', this.NodeViz.setOffsets.bind(this));
	},

	showTooltip: function(label) {
		if (typeof(this.state) != 'undefined' && this.state !== '') { return; }
		if(label != '') { 
			if (! this.NodeViz.offsetY) { 
				this.NodeViz.setOffsets();
			}
			tooltip = $('tooltip');
			tooltip.innerHTML = label;
			tooltip.style.visibility='visible'; //show the tooltip
		}
	},

	hideTooltip: function() { 
		$('tooltip').style.visibility='hidden'; //hide the tooltip first - somehow this makes it faster
		$('images').style.cursor = 'default';	
	},
	highlightNode: function(id, noshowtooltip) {
		if (! noshowtooltip) { 
			id = id.toString();
			this.showTooltip(this.NodeViz.data.nodes[id].tooltip); //Set the tooltip contents
		}
	},
	unhighlightNode: function(id) { 
		this.hideTooltip();
	},
	selectNode: function(id) {
	},
	unselectNode: function(id) {
	},
};
