/**
 *  SVGPan library 1.2
 * ====================
 *
 * Given an unique existing element with id "graphgraph0", including the
 * the library into any SVG adds the following capabilities:
 *
 *  - Mouse panning
 *  - Mouse zooming (using the wheel)
 *  - Object dargging
 *
 * Known issues:
 *
 *  - Zooming (while panning) on Safari has still some issues
 *
 * Releases:
 *
 * 1.2, Sat Mar 20 08:42:50 GMT 2010, Zeng Xiaohui
 *	Fixed a bug with browser mouse handler interaction
 *
 * 1.1, Wed Feb  3 17:39:33 GMT 2010, Zeng Xiaohui
 *	Updated the zoom code to support the mouse wheel on Safari/Chrome
 *
 * 1.0, Andrea Leofreddi
 *	First release
 *
 * This code is licensed under the following BSD license:
 *
 * Copyright 2009-2010 Andrea Leofreddi <a.leofreddi@itcharm.com>. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 * 
 *    1. Redistributions of source code must retain the above copyright notice, this list of
 *       conditions and the following disclaimer.
 * 
 *    2. Redistributions in binary form must reproduce the above copyright notice, this list
 *       of conditions and the following disclaimer in the documentation and/or other materials
 *       provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY Andrea Leofreddi ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL Andrea Leofreddi OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * The views and conclusions contained in the software and documentation are those of the
 * authors and should not be interpreted as representing official policies, either expressed
 * or implied, of Andrea Leofreddi.
 */

var GraphSVGZoom = Class.create();

GraphSVGZoom.prototype = {
	initialize: function(GraphSVG) {
		this.GraphSVG = GraphSVG;
		$(this.GraphSVG.graphdiv).innerHTML += this.zoomControlsHTML;
	},
	reset: function() {
		this.state = 'none';
		this.stateOrigin = '';
		this.zoomlevels = 8;
		this.zoom_delta = .8;
		this.current_zoom = 1;
		if (this.zoomSlider) { 
			this.zoomSlider.setValue(this.current_zoom);
		}
		Event.stopObserving('zoomin');
		Event.stopObserving('zoomout');
		Event.stopObserving('zoomreset');
		Event.stopObserving('zoomSlider');
		Event.stopObserving('zoomHandle');
		Event.stopObserving(this.root);
	},

/**
 * Register handlers
 */
	setupListeners: function(root){
		this.root = root;
		this.stateTf = $('graph0').getCTM().inverse();
		Event.observe($('svg'), 'mousedown', function(e) { this.handleMouseDown(e); }.bind(this));
		Event.observe($('svg'), 'mousemove', function(e) { this.handleMouseMove(e); }.bind(this));
		Event.observe($('svg'), 'mouseup', function(e) { this.handleMouseUp(e); }.bind(this));
		Event.stopObserving('svgscreen', 'click');
		Event.observe($('svgscreen'), 'mouseup', function(e) { this.handleMouseUp(e); }.bind(this));
		//Event.observe($('svg'), 'mouseout', function(e) { this.handleMouseUp(e); }.bind(this));
		Event.observe($('zoomin'), 'click', function(e) { this.zoom('in'); }.bind(this));
		Event.observe($('zoomout'), 'click', function(e) { this.zoom('out'); }.bind(this));
		Event.observe($('zoomreset'), 'click', function(e) { this.zoom('reset'); }.bind(this));
		if(navigator.userAgent.toLowerCase().indexOf('webkit') >= 0) {
			Event.observe(root, 'mousewheel', function(e) { this.handleMouseWheel(e); }.bind(this)); // Chrome/Safari
		} else {
			Event.observe(root, 'DOMMouseScroll', function(e) { this.handleMouseWheel(e); }.bind(this)); // Chrome/Safari
		}

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
					this.zoom(value);
				}
			}.bind(this),
			onSlide: function(value) {
				if(this.current_zoom != value) { 
					this.zoom(value);
				}
			}.bind(this),
		});
		this.center = {'x': ($('svg').childNodes[0].getBBox().width/2), 'y':($('svg').childNodes[0].getBBox().height/2)};
		this.resetsvg = $('graph0').getCTM();
	},

/**
 * Instance an SVGPoint object with given event coordinates.
 */
	getEventPoint: function(evt) {
		var p = this.root.createSVGPoint();

		p.x = evt.clientX;
		p.y = evt.clientY;
		var offset = $('svg').viewportOffset();
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
		$('fgraph0').setAttribute("transform", s);
		this.zoomSlider.setValue(this.current_zoom);
		if(this.current_zoom >= 2) {
			$('img0').addClassName('showtwo');
			$('svg').addClassName('showtwo');
		} else {
			$('img0').removeClassName('showtwo');
			$('svg').removeClassName('showtwo');
		}

		if(this.current_zoom >= 4) {
			$('img0').addClassName('showall');
			$('svg').addClassName('showall');
		} else {
			$('img0').removeClassName('showall');
			$('svg').removeClassName('showall');
		}
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

		var svgDoc = evt.target.ownerDocument;

		var delta;

		if(evt.wheelDelta)
			delta = evt.wheelDelta / 360; // Chrome/Safari
		else
			delta = evt.detail / -9; // Mozilla
		if (delta > 0) { 
			this.current_zoom++;
			delta = 1/3;
		} else { 
			this.current_zoom--;
			delta = -1/3;
		}
		if (this.current_zoom < 0) {
			this.current_zoom = 0;
			this.state = '';
			return;
		} else if (this.current_zoom > this.zoomlevels) { 
			this.current_zoom = this.zoomlevels;
			this.state = '';
			return;
		}
		var z = Math.pow(1 + this.zoom_delta, delta);

		var g = svgDoc.getElementById("graph0");
		
		var p = this.getEventPoint(evt);

		p = p.matrixTransform(g.getCTM().inverse());

		// Compute new scale matrix in current mouse position
		var k = this.root.createSVGMatrix().translate(p.x, p.y).scale(z).translate(-p.x, -p.y);

		this.setCTM(g, g.getCTM().multiply(k));

		this.stateTf = this.stateTf.multiply(k.inverse());
		this.state = '';
	},

/**
 * Handle mouse move event.
 */
	handleMouseMove: function(evt) {
		if(evt.preventDefault)
			evt.preventDefault();

		evt.returnValue = false;

		var g = $('graph0');

		if(this.state == 'pan') {
			// Pan mode
			var p = this.getEventPoint(evt).matrixTransform(this.stateTf);

			this.setCTM(g, this.stateTf.inverse().translate(p.x - this.stateOrigin.x, p.y - this.stateOrigin.y));
		}
		/* else if(state == 'move') {
			// Move mode
			var p = getEventPoint(evt).matrixTransform(g.getCTM().inverse());

			setCTM(stateTarget, root.createSVGMatrix().translate(p.x - stateOrigin.x, p.y - stateOrigin.y).multiply(g.getCTM().inverse()).multiply(stateTarget.getCTM()));

			stateOrigin = p;
		}*/
	},

/**
 * Handle click event.
 */
	handleMouseDown: function(evt) {
		if(evt.preventDefault)
			evt.preventDefault();

		evt.returnValue = false;

		var svgDoc = this.root;
		if (evt.target.id != 'svgscreen' && evt.target.tagName != 'svg') { 
			return;
		}
		var g = $('graph0');
		//g.style.setProperty('display', 'none', "");
		//g.hide();

		//if(evt.target.tagName == "svg" || 1) {
			// Pan mode
			this.state = 'pan';

			this.stateTf = g.getCTM().inverse();

			this.stateOrigin = this.getEventPoint(evt).matrixTransform(this.stateTf);
		//} else {
			// Move mode
		//	state = 'move';

		//	stateTarget = evt.target;

		//	stateTf = g.getCTM().inverse();

		//	stateOrigin = getEventPoint(evt).matrixTransform(stateTf);
		//}
	},

/**
 * Handle mouse button release event.
 */
	handleMouseUp: function(evt) {
		if(evt.preventDefault)
			evt.preventDefault();

		evt.returnValue = false;

		var origin = this.getEventPoint(evt).matrixTransform(this.stateTf);
		if (this.stateOrigin.x == origin.x && this.stateOrigin.y == origin.y) { 
			this.GraphSVG.Framework.unselectNode(1);
		}

		if(this.state == 'pan' || this.state == 'move') {
			$('graph0').style.removeProperty('display');
			// Quit pan mode
			this.state = '';
		}
	},
	zoom: function(d) {
		var previouszoom = this.current_zoom;
		if (d == 'in') { 
			d = ++this.current_zoom;
		} else if (d == 'out') { 
			d = --this.current_zoom;
		} else if (d == 'reset') {
			this.current_zoom = 1;
			this.setCTM($('graph0'), this.resetsvg);
			return;
		}
		if (d < 0 || d > this.zoomlevels) { 
			return;
		}
		this.current_zoom = d;
		var zoom_amount = d - previouszoom;

		var delta = 0.3333333333333333;
		var z = Math.pow(1 + this.zoom_delta, delta);
		var g = $("graph0");
		//var p = {'x': ($('svg').childNodes[0].getBBox().width/2), 'y':($('svg').childNodes[0].getBBox().height/2)};
		var p = this.root.createSVGPoint();
		var dimensions = $(this.GraphSVG.Framework.graphdiv).getDimensions();
		p.x = this.center.x;
		p.y = this.center.y;
		p = p.matrixTransform(g.getCTM().inverse());
		z = Math.pow(z, zoom_amount);
		var k = this.root.createSVGMatrix().translate(p.x, p.y).scale(z).translate(-p.x, -p.y);
		this.setCTM(g, g.getCTM().multiply(k));

		if(! this.stateTf) { 
			this.stateTf = $('graph0').getCTM().inverse();
		}
		this.stateTf = this.stateTf.multiply(k.inverse());
	},

	zoomControlsHTML: "\
		<div id='zoomcontrols'>\
			<span id='zoomin' class='zoomin' alt='Zoom In' title='Zoom In'>[+]</span>\
			<div id='zoomSlider' class='slider'><div id='zoomHandle' class='handle'></div></div>\
			<span id='zoomout' class='zoomout' alt='Zoom Out' title='Zoom Out'>[-]</span>\
			<span id='zoomreset' class='zoomreset' alt='Reset Zoom' title='Reset Zoom'>[0]</span>\
		</div>\
	",	
}


