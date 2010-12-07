
var GraphImage = Class.create();

GraphImage.prototype = {
	initialize: function(framework) {
		this.Framework = framework;
		this.graphdiv = this.Framework.graphdiv;
		if (! $('tooltip')) { 
			$(document.body).insert({ top: new Element('div', {'id': 'tooltip'}) });
		}
		if (! $('highlight')) { 
			$(document.body).insert({ top: new Element('div', {'id': 'tooltip'}) });
		}
		$(this.graphdiv).innerHTML += "<div id='images'></div>";
		$(this.graphdiv).innerHTML += "<div id='imagescreen' style='display:none;'></div>";
	},
	reset: function() {
	},
	renderGraph: function(img, overlay) {

	},
	//Catches mousemove events
	mousemove: function(e) {
		if($('tooltip').style.visibility == 'visible') { 
			var mousepos = { 'x': Event.pointerX(e), 'y': Event.pointerY(e) };
			$('tooltip').setStyle({'top': (mousepos['y']- this.tooltipOffsetY - $('tooltip').getHeight()) + 'px', 'left': (mousepos['x']  - this.tooltipOffsetX) + 'px'});
		}
	},
	setupListeners: function() { 
		Event.observe($('img0'), 'mousemove', this.mousemove.bind(this));
		//Event.observe($('info'), 'mousemove', this.mousemove);
		Event.observe($('tooltip'), 'mousemove', this.mousemove.bind(this))
		//Event.observe($('highlight'), 'mouseover', this.highlightNode);
		Event.observe($('tooltip'), 'mouseout', this.hideTooltip.bind(this));
		//Event.observe($('highlight'), 'mousedown', this.selectNode);
		Event.observe(window, 'resize', this.setOffsets );
		//window.setTimeout("setOffsets()", 250);
	},

	showTooltip: function(label) {
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
		this.Framework.current['node'] = "";
		this.hideTooltip();
	},
	selectNode: function(id) {
	},
	unselectNode: function(id) {
	},
};
