var GraphSVG = Class.create(GraphImage, {
	initialize: function($super, framework) {
		$super(framework);
		if (this.Framework.useSVG != 1 ) { return; } 
		this.GraphSVGZoom = new GraphSVGZoom(this);
	},
	reset: function($super) {
		$super();
		this.GraphSVGZoom.reset();
		$$('.node').each(function(e) { Element.stopObserving(e); });
		$$('.edge', '#svg', '#graphs', '#svgscreen').each(function(e) { Element.stopObserving(e); });
	},
	render: function($super, responseData) { 
		//console.time('renderSVG');
		$super();
		var image = responseData.image;
		var overlay = responseData.overlay;
		overlay = overlay.replace("<div id='svg_overlay' style='display: none'>", '');
		overlay = overlay.replace("</div>", '');

		//parse the SVG into a new document
		var dsvg = new DOMParser();
		dsvg.async = false;
		var svgdoc = dsvg.parseFromString(overlay, 'text/xml');

		//insert the underlying image
		$('images').update(new Element('div', {'id': 'image'}));
		$('image').appendChild($('image').ownerDocument.importNode(svgdoc.firstChild, true));

		//reset all the ids for the under-image
		$('svgscreen').setAttribute('id', 'underlay_svgscreen');
		$A($('images').getElementsByTagName('g')).each( function(g) { 
			var id = $(g).getAttribute('id');
			$(g).setAttribute('id', 'underlay_'+id);
		});

		//insert the svg image again as the overlay
		$('images').insert(new Element('div', {'id': 'svg_overlay'}));
		$('svg_overlay').appendChild($('svg_overlay').ownerDocument.importNode(svgdoc.firstChild, true));


		$('svg_overlay').style.setProperty('position','absolute', '');
		$('svg_overlay').style.setProperty('top','0px', '');
		$('svgscreen').style.setProperty('cursor','move', 'important');
		$('graph0').style.setProperty('opacity', '1', '');
		$('svg_overlay').style.setProperty('visibility', 'visible', '');
		$('svg_overlay').style.setProperty('display', 'block','');
		this.setupListeners();

		//apply the initial filter - this should probably by handled in GraphSVGZoom, but where?
		$('image').addClassName('zoom_'+this.GraphSVGZoom.current_zoom);
		$('svg_overlay').addClassName('zoom_'+this.GraphSVGZoom.current_zoom);
		//console.timeEnd('renderSVG');
	},
	setupListeners: function($super) {
		$super();
		Event.observe($('svgscreen'),'click', this.Framework.unselectNode.bind(this.Framework));
		Event.observe($('svg_overlay'), 'mousemove', this.mousemove.bind(this));
		Event.observe($('graphs'), 'mousemove', this.mousemove.bind(this));
		$$('#svg_overlay .node').each( function(n) {
			var nodeid = n.id;
			var node = this.Framework.data.nodes[n.id]
			if (node['zoom']) { 
				this.addClassName($(nodeid), 'zoom_'+node['zoom']);
				this.addClassName($('underlay_'+nodeid), 'zoom_'+node['zoom']);
			}
			if (node['class']) { 
				node['class'].split(' ').each( function(c) { 
					this.addClassName($(nodeid), c);	
					this.addClassName($('underlay_'+nodeid), c);	
				}, this);
			}
			if (n.childNodes[1]) {
				Event.observe(n,'mouseover', function(e) { eval(node.onMouseover); }.bind(this));
				Event.observe(n,'mouseout', function(e) { this.Framework.unhighlightNode(n.id); }.bind(this));
				Event.observe(n,'click', function(e) { eval(node.onClick); }.bind(this));
			}
		}, this);
		$$('#svg_overlay .edge').each( function(n) {
			var edgeid = n.id;
			var edge = this.Framework.data.edges[edgeid];
			if (edge['zoom']) { 
				this.addClassName($(edgeid), 'zoom_'+edge['zoom']);
				this.addClassName($('underlay_'+edgeid), 'zoom_'+edge['zoom']);
			}
			if (edge['class']) { 
				edge['class'].split(' ').each( function(c) { 
					this.addClassName($(edgeid), c);	
					this.addClassName($('underlay_'+edgeid), c);	
				}, this);
			}
			Event.observe(n,'mouseover', function(eventObject) { eval(edge.onMouseover); }.bind(this));
			Event.observe(n,'mouseout', this.hideTooltip.bind(this));
			Event.observe(n,'click', function(eventObject) { eval(edge.onClick); }.bind(this));
			//Event.observe(n,'click', graphviz[n.id].onClick);
		}, this);
		this.GraphSVGZoom.setupListeners($('svg_overlay').childNodes[0]);
	},
	highlightNode: function($super, id, text, noshowtooltip) {
		$super(id, text, noshowtooltip);
		var node = $(id).childNodes[3];
		node.setAttribute('class', 'nhighlight');
		if (this.Framework.current['network']) { 
			//$(id).parentNode.appendChild($(id));
		}
		this.showSVGElement(id);
	},

	unhighlightNode: function($super, id) {
		$super(id);
		var framework = this.Framework;
		if (! framework.useSVG) { return; }
		var node = $(id);
		node.childNodes[3].removeAttribute('class');
		if (framework.current['network'] == id || (framework.data.nodes[framework.current['network']] && framework.data.nodes[framework.current['network']]['relatedNodes'][id])) {
			return;
		} else {
			this.hideSVGElement(node);
		}
	},

	showNetwork: function(id) {
		var node = $(id);
		if (! node) { return; }
		if (this.Framework.current['network'] == node.id) { 
			this.hideNetwork();
			this.highlightNode(node.id);
			return;
		}
		if (this.Framework.current['edge']) { 
			this.hideEdge(1);
		}
		if (this.Framework.current['network']) { 
			this.hideNetwork(1);
		}
		if ($('image').getOpacity() == 1) {
			new Effect.Opacity('image', { from: 1, to: .3, duration: .5});
		}
		showSVGElement(node);
		$H(nodelookup[node.id]['edges']).keys().each(function(e) {
			this.showSVGElement(e);
		}, this);
		$H(nodelookup[node.id]['lnodes']).keys().each(function(e) {
			this.showSVGElement(e);
		}, this);
		this.Framework.current['network'] = node.id	
	},

	showSVGElement: function(e) {
		if (! $(e)) { return; }
		$(e).style.setProperty('opacity', '1', 'important');
		$(e).style.setProperty('display', 'block', 'important');
	},

	hideSVGElement: function(e) {
		if (! $(e)) { return; }
		$(e).style.removeProperty('opacity');
		$(e).style.removeProperty('display');
	},
	selectNode: function($super, id) { 
		$super();
		this.showSVGElement(id);
		this.addClassName($(id), 'selected');
		if ($('image').getOpacity() == 1) {
			new Effect.Opacity('image', { from: 1, to: .3, duration: .5});
		}
		$H(this.Framework.data.nodes[id].relatedNodes).keys().each(function(e) {
			this.showSVGElement(e);
			this.addClassName($(e), 'oselected');
			$H(this.Framework.data.nodes[id].relatedNodes[e]).values().each( function(edge) { 
				this.showSVGElement(edge);
			}, this);
		}, this);
	},
	unselectNode: function($super, id, fade) { 
		$super();
		if (fade) {
			new Effect.Opacity('image', { from: .3, to: 1, duration: .3});
		}
		this.removeClassName($(id), 'selected');
		$H(this.Framework.data.nodes[id].relatedNodes).keys().each(function(e) {
			this.hideSVGElement(e);
			this.removeClassName($(e), 'oselected');
			$H(this.Framework.data.nodes[id].relatedNodes[e]).values().each( function(edge) { 
				this.hideSVGElement(edge);
			}, this);
		}, this);
		this.hideSVGElement(id);
		this.unhighlightNode(id);
	},
	hasClassName: function(element, className) {
		var elementClassName = element.getAttribute('class');
		return (elementClassName.length > 0 && (elementClassName == className ||
		new RegExp("(^|\\s)" + className + "(\\s|$)").test(elementClassName)));
	},

	addClassName: function(element, className) {
		var elementClassName = element.getAttribute('class');
		if (!this.hasClassName(element, className)) {
			elementClassName += (elementClassName ? ' ' : '') + className;
		}
		element.setAttribute('class', elementClassName);
		return element;
	},

	removeClassName: function(element, className) {
		elementClassName = element.getAttribute('class');
		elementClassName = elementClassName.replace(
		new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
		element.setAttribute('class', elementClassName);
		return element;
	},

	zoomToNode: function(id, level) {
		//Change graph zoom level, centered on center of a node
	
  		//get the transform of the svg coords to screen coords
  		var ctm = $(id).getScreenCTM();
	
  		//get bounding box for node 
  		var box = $(id).getBBox();
  		var svg_p = this.GraphSVGZoom.root.createSVGPoint();
  		svg_p.x = box.width /2 + box.x;
		svg_p.y = box.height /2  + box.y;

		//use the transform to move the point to screen coords
		dom_p = svg_p.matrixTransform(ctm);
		//correct for differences in screen coords when page is scrolled
		var offset = $('svg_overlay').viewportOffset();
		dom_p.x = dom_p.x -offset[0];
		dom_p.y = dom_p.y -offset[1];

		//if no level was passed, default to 'in'
		level = level != '' ? level : 'in'
  		this.GraphSVGZoom.zoom(level,dom_p);
	},

	panToNode: function(id, zoom) {
		//re-center graph on center of a node, and optionally change zoom level
	
  		//get the transform of the svg coords to screen coords
  		var ctm = $(id).getScreenCTM();
		var g = $('graph0');
		this.GraphSVGZoom.stateTf = $('graph0').getCTM().inverse();

  		//get bounding box for node 
  		var box = $(id).getBBox();
  		var node_center = this.GraphSVGZoom.root.createSVGPoint();
  		node_center.x = box.width /2 + box.x;
		node_center.y = box.height /2 + box.y;

		var center = this.GraphSVGZoom.calculateCenter();
		//$('images').insert(new Element('div', {'id': 'centertest', 'style': 'position: absolute; opacity: .2; background: pink; z-index: 1000; top: 0px; left: 0px; width:'+center.x+'px; height: '+center.y+'px;'}));
		//convert from dom pixels to svg units
		center = center.matrixTransform(this.GraphSVGZoom.stateTf);

		//now let's calculate the delta
		var delta = this.GraphSVGZoom.root.createSVGPoint();
		delta.x = (center.x - node_center.x);
		delta.y = (center.y - node_center.y);

		this.GraphSVGZoom.setCTM(g, $('graph0').getCTM().translate(delta.x, delta.y));

		if (typeof(zoom) != 'undefined') {
			this.zoomToNode(id, zoom);
		}
	},
});
