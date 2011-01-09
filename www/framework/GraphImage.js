
var GraphImage = Class.create();

GraphImage.prototype = {
	initialize: function(framework) {
		this.Framework = framework;
		this.graphdiv = this.Framework.graphdiv;
		if (! $('tooltip')) { 
			$(document.body).insert({ top: new Element('div', {'id': 'tooltip'}) });
		}
		$(this.graphdiv).innerHTML += "<div id='images'></div>";
		$(this.graphdiv).innerHTML += "<div id='imagescreen' style='display:none;'></div>";
		this.graphDimensions = $(this.graphdiv).getDimensions();
	},
	reset: function() {
		Event.stopObserving($('img0'));
		this.graphDimensions = $(this.graphdiv).getDimensions();
	},
	render: function(responseData) {
	},
	appendOptions: function() {
		this.Framework.params.useSVG = this.Framework.useSVG;
		this.Framework.params.graphWidth = this.graphDimensions.width;
		this.Framework.params.graphHeight = this.graphDimensions.height;
	},
	//Catches mousemove events
	mousemove: function(e) {
		if($('tooltip').style.visibility == 'visible') { 
			var mousepos = { 'x': Event.pointerX(e), 'y': Event.pointerY(e) };
			$('tooltip').setStyle({'top': (mousepos['y']- this.Framework.tooltipOffsetY - $('tooltip').getHeight()) + 'px', 'left': (mousepos['x']  - this.Framework.tooltipOffsetX) + 'px'});
		}
	},
	setupListeners: function() { 
		Event.observe($('img0'), 'mousemove', this.mousemove.bind(this));
		Event.observe($('tooltip'), 'mousemove', this.mousemove.bind(this))
		Event.observe($('tooltip'), 'mouseout', this.hideTooltip.bind(this));
		Event.observe(window, 'resize', this.Framework.setOffsets.bind(this));
	},

	showTooltip: function(label) {
		if (! this.Framework.offsetY) { 
			this.Framework.setOffsets();
		}
		tooltip = $('tooltip');
		if(label) { 
			tooltip.innerHTML = label;
		}
		tooltip.style.visibility='visible'; //show the tooltip
	},

	hideTooltip: function() { 
		$('tooltip').style.visibility='hidden'; //hide the tooltip first - somehow this makes it faster
		$('images').style.cursor = 'default';	
	},
	highlightNode: function(id, noshowtooltip) {
		if (! noshowtooltip) { 
			id = id.toString();
			this.showTooltip(this.Framework.data.nodes[id].tooltip); //Set the tooltip contents
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
