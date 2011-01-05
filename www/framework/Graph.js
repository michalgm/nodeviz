
var Graph = Class.create();

Graph.prototype = {
	initialize: function(framework, graphdiv, listdiv) {
		this.graphdiv = graphdiv;
		this.listdiv = listdiv;
		this.Framework = framework;
		this.data = [];
		if (document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1")) { 
			this.useSVG = 1;
		} else {
			this.useSVG = 0;
		}
		this.setupGraphDiv();
		this.graphDimensions = $(graphdiv).getDimensions();
		this.reloadGraph({'useSVG': this.useSVG, 'setupfile': 'FECCanComGraph'});
	},
};

