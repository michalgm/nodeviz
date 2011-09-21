var GraphSVG = Class.create(GraphImage, {
	initialize: function($super, framework) {
		$super(framework);
		if (this.Framework.useSVG != 1 ) { return; } 
		//this.GraphSVGZoom = new GraphSVGZoom(this);
		$(this.graphdiv).innerHTML += this.zoomControlsHTML;
	},
	reset: function($super) {
		$super();
		this.state = 'none';
		this.stateOrigin = '';
		this.zoomlevels = 8;
		this.zoom_delta = .8;
		this.current_zoom = 1;
		this.previous_zoom = 1;
		this.zoom_point = null;
		if (this.zoomSlider) { 
			this.zoomSlider.setValue(this.current_zoom);
		}
		Event.stopObserving('zoomin');
		Event.stopObserving('zoomout');
		Event.stopObserving('zoomreset');
		Event.stopObserving('zoomSlider');
		Event.stopObserving('zoomHandle');
		Event.stopObserving(this.root);
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
		overlay = overlay.replace(/<svg width=\"[\d\.]+px\" height=\"[\d\.]+px\"/, "<svg width=\""+this.graphDimensions.width+"\" height=\""+this.graphDimensions.height+"\"");
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
		$('graph0').style.setProperty('opacity', '1', '');
		$('svg_overlay').style.setProperty('visibility', 'visible', '');
		$('svg_overlay').style.setProperty('display', 'block','');
		this.setupListeners();

		//apply the initial filter - this should probably by handled in GraphSVGZoom, but where?
		$('image').addClassName('zoom_'+this.current_zoom);
		$('svg_overlay').addClassName('zoom_'+this.current_zoom);
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
				Event.observe(n,'mouseup', function(e) { 
					var origin = this.getEventPoint(e).matrixTransform(this.stateTf);
					if (this.stateOrigin.x == origin.x && this.stateOrigin.y == origin.y) {
						eval(node.onClick);
					}
				}.bind(this));
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
			Event.observe(n,'mouseup', function(e) { 
				var origin = this.getEventPoint(e).matrixTransform(this.stateTf);
				if (this.stateOrigin.x == origin.x && this.stateOrigin.y == origin.y) {
					eval(edge.onClick);
				}
			}.bind(this));
			//Event.observe(n,'click', graphviz[n.id].onClick);
		}, this);
		this.setupZoomListeners($('svg_overlay').childNodes[0]);
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
  		var svg_p = this.root.createSVGPoint();
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
  		this.zoom(level,dom_p);
	},

	panToNode: function(id, zoom) {
		//re-center graph on center of a node, and optionally change zoom level
	
  		//get the transform of the svg coords to screen coords
  		var ctm = $(id).getScreenCTM();
		var g = $('graph0');
		this.stateTf = $('graph0').getCTM().inverse();

  		//get bounding box for node 
  		var box = $(id).getBBox();
  		var node_center = this.root.createSVGPoint();
  		node_center.x = box.width /2 + box.x;
		node_center.y = box.height /2 + box.y;

		var center = this.calculateCenter();
		//$('images').insert(new Element('div', {'id': 'centertest', 'style': 'position: absolute; opacity: .2; background: pink; z-index: 1000; top: 0px; left: 0px; width:'+center.x+'px; height: '+center.y+'px;'}));
		//convert from dom pixels to svg units
		center = center.matrixTransform(this.stateTf);

		//now let's calculate the delta
		var delta = this.root.createSVGPoint();
		delta.x = (center.x - node_center.x);
		delta.y = (center.y - node_center.y);

		this.setCTM(g, $('graph0').getCTM().translate(delta.x, delta.y));

		if (typeof(zoom) != 'undefined') {
			this.zoomToNode(id, zoom);
		}
	},
	setupZoomListeners: function(root){
		this.root = root;
		this.stateTf = $('graph0').getCTM().inverse();
		Event.observe($('svgscreen'), 'mousedown', function(e) { this.handleMouseDown(e); }.bind(this));
		Event.observe($('svgscreen'), 'mousemove', function(e) { this.handleMouseMove(e); }.bind(this));
		Event.observe($('svgscreen'), 'mouseup', function(e) { this.handleMouseUp(e); }.bind(this));
		Event.observe(root, 'mousedown', function(e) { this.handleMouseDown(e); }.bind(this));
		Event.observe(root, 'mousemove', function(e) { this.handleMouseMove(e); }.bind(this));
		Event.observe(root, 'mouseup', function(e) { this.handleMouseUp(e); }.bind(this));
		Event.stopObserving('svgscreen', 'click');
		Event.observe($('svgscreen'), 'mouseup', function(e) { this.handleMouseUp(e); }.bind(this));
		//Event.observe($('svg_overlasvg_overlay'), 'mouseout', function(e) { this.handleMouseUp(e); }.bind(this));
		Event.observe($('zoomin'), 'click', function(e) { this.zoom('in'); }.bind(this));
		Event.observe($('zoomout'), 'click', function(e) { this.zoom('out'); }.bind(this));
		Event.observe($('zoomreset'), 'click', function(e) { this.zoom('reset'); }.bind(this));
		if(navigator.userAgent.toLowerCase().indexOf('webkit') >= 0) {
			Event.observe(root, 'mousewheel', function(e) { this.handleMouseWheel(e); }.bind(this)); // Chrome/Safari
		} else {
			Event.observe(root, 'DOMMouseScroll', function(e) { this.handleMouseWheel(e); }.bind(this)); // Chrome/Safari
		}
		Event.observe(root, 'dblclick', function(e) { this.zoom('in', this.getEventPoint(e)); }.bind(this));
		var defaultValue = this.current_zoom;
		var x = 0;
		var values = new Array();
		while (x <= this.zoomlevels) { 
			values.unshift(x);
			x++;
		}
		this.zoomSlider = new Control.Slider('zoomHandle', 'zoomSlider', {values: values, range: $R(this.zoomlevels,0), sliderValue: defaultValue,
			onChange: function(value) { 
				if(this.current_zoom != value) { 
					this.SVGzoom(value);
				}
			}.bind(this),
		});
		this.center = this.calculateCenter();
		this.resetsvg = $('graph0').getCTM();
	},
/**
 * Instance an SVGPoint object with given event coordinates.
 */
	getEventPoint: function(evt) {
		var p = this.root.createSVGPoint();

		p.x = evt.clientX;
		p.y = evt.clientY;
		var offset = $('svg_overlay').viewportOffset();
		p.x = p.x -offset[0];
		p.y = p.y  - offset[1];

		return p;
	},

/**
 * Sets the current transform matrix of an element.
 */
	setCTM: function(element, matrix) {
		var s = "matrix(" + matrix.a + "," + matrix.b + "," + matrix.c + "," + matrix.d + "," + matrix.e + "," + matrix.f + ")";

		element.setAttribute("transform", s);
		$('underlay_graph0').setAttribute("transform", s);
		//this.zoomSlider.setValue(this.current_zoom);
		$('image').removeClassName('zoom_'+this.previous_zoom);
		$('svg_overlay').removeClassName('zoom_'+this.previous_zoom);
		$('image').addClassName('zoom_'+this.current_zoom);
		$('svg_overlay').addClassName('zoom_'+this.current_zoom);
	},

/**
 * Dumps a matrix to a string (useful for debug).
 */
	dumpMatrix: function(matrix) {
		var s = "[ " + matrix.a + ", " + matrix.c + ", " + matrix.e + "\n  " + matrix.b + ", " + matrix.d + ", " + matrix.f + "\n  0, 0, 1 ]";

		return s;
	},

/**
 * Sets attributes of an element.
 */
	setAttributes: function(element, attributes){
		for (i in attributes) {
			element.setAttributeNS(null, i, attributes[i]);
		}
	},

/**
 * Handle mouse move event.
 */
	handleMouseWheel: function(evt) {
		if (this.state == 'zoom') { 
			return;
		}
		this.state = 'zoom';

		if(evt.preventDefault) {
			evt.preventDefault();
		}

		evt.returnValue = false;

		var delta;
		if(evt.wheelDelta)
			delta = evt.wheelDelta / 360; // Chrome/Safari
		else
			delta = evt.detail / -9; // Mozilla

		var p = this.getEventPoint(evt);
		if (delta > 0) { 
			this.zoom('in', p);
		} else { 
			this.zoom('out', p);
		}
		this.state = '';
	},

/**
 * Handle mouse move event.
 */
	handleMouseMove: function(evt) {
		if(evt.preventDefault) {
			evt.preventDefault();
		}
		evt.returnValue = false;

		var g = $('graph0');

		if(this.state == 'pan') {
			var p = this.getEventPoint(evt).matrixTransform(this.stateTf);
			this.setCTM(g, this.stateTf.inverse().translate(p.x - this.stateOrigin.x, p.y - this.stateOrigin.y));
		}
	},

/**
 * Handle click event.
 */
	handleMouseDown: function(evt) {
		if(evt.preventDefault)
			evt.preventDefault();

		evt.returnValue = false;

		var svgDoc = this.root;
		//I'm removing this so that we can drag beyond edge of graph. Hopefully it didn't do anything?
		if (evt.target.id != 'svgscreen' && evt.target.tagName != 'svg_overlay' && evt.target.tagName != 'svg') { 
			//return;
		}
		var g = $('graph0');

		this.state = 'pan';
		evt.target.style.cursor = 'move';

		this.stateTf = g.getCTM().inverse();

		this.stateOrigin = this.getEventPoint(evt).matrixTransform(this.stateTf);
	},

/**
 * Handle mouse button release event.
 */
	handleMouseUp: function(evt) {
		if(evt.preventDefault) {
			evt.preventDefault();
		}
		evt.returnValue = false;

		var origin = this.getEventPoint(evt).matrixTransform(this.stateTf);
		if (this.stateOrigin.x == origin.x && this.stateOrigin.y == origin.y && (evt.target.id == 'svgscreen' || evt.target.tagName == 'svg_overlay' || evt.target.tagName == 'svg')) { 
			this.Framework.unselectNode(1);
		}

		if(this.state == 'pan') {
			$('graph0').style.removeProperty('display');
			evt.target.style.removeProperty('cursor');
			// Quit pan mode
		}
		this.state = '';
		//evt.stop();
	},
	zoom: function(d, p) {
		if (d == 'in') { 
			d = this.current_zoom+ 1;
		} else if (d == 'out') { 
			d = this.current_zoom- 1;
		} else if (d == 'reset') {
			d=1;
			//this.current_zoom = 1;
			this.zoomSlider.setValue(1);
			this.setCTM($('graph0'), this.resetsvg);
			return;
		}
		if (d < 0 || d > this.zoomlevels) {
			return;
		}
		if (p) { 
			this.zoom_point = p;
		}
		this.zoomSlider.setValue(d);
	},
	SVGzoom: function(d) { 
		this.previous_zoom = this.current_zoom;
		this.current_zoom = d;
		var zoom_amount = d - this.previous_zoom;
		var delta = 0.3333333333333333;
		var z = Math.pow(1 + this.zoom_delta, delta);
		var g = $("graph0");
		var center = '';
		if (! this.zoom_point) { 
			center = this.calculateCenter();
		} else { 
			center = this.zoom_point;
		}
		p = center.matrixTransform(g.getCTM().inverse());
		z = Math.pow(z, zoom_amount);
		var k = this.root.createSVGMatrix().translate(p.x, p.y).scale(z).translate(-p.x, -p.y);
		this.setCTM(g, g.getCTM().multiply(k));

		if(! this.stateTf) { 
			this.stateTf = $('graph0').getCTM().inverse();
		}
		this.stateTf = this.stateTf.multiply(k.inverse());
		this.zoom_point = null;

	},
	calculateCenter: function() {
  		var center = this.root.createSVGPoint();
		center.x = $('svg_overlay').positionedOffset()[0] + ($('svg_overlay').getWidth() /2);
		center.y = $('svg_overlay').positionedOffset()[1] + ($('svg_overlay').getHeight() /2);
		return center;
	},
	zoomControlsHTML: "\
		<div id='zoomcontrols'>\
			<span id='zoomin' class='zoomin' alt='Zoom In' title='Zoom In'>[+]</span>\
			<div id='zoomSlider' class='slider'><div id='zoomHandle' class='handle'></div></div>\
			<span id='zoomout' class='zoomout' alt='Zoom Out' title='Zoom Out'>[-]</span>\
			<span id='zoomreset' class='zoomreset' alt='Reset Zoom' title='Reset Zoom'>[0]</span>\
		</div>\
	",	});
