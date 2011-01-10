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
		$super();
		var image = responseData.img;
		var overlay = responseData.overlay;
		$('images').update(overlay);
		$('svg_overlay').setAttribute('id', 'image');
		$('svgscreen').setAttribute('id', 'fsvgscreen');
		$A($('image').getElementsByTagName('g')).each( function(g) { 
			var id = $(g).getAttribute('id');
			$(g).setAttribute('id', 'f'+id);
		});
		$('images').innerHTML += overlay;
		$('image').show();

		$('svg_overlay').style.setProperty('position','absolute', '');
		//$('svg_overlay').clonePosition($('image'));
		$('svg_overlay').style.setProperty('top','0px', '');
		$('svgscreen').style.setProperty('cursor','move', 'important');
		$('graph0').style.setProperty('opacity', '1', '');
		$('svg_overlay').style.setProperty('visibility', 'visible', '');
		//var left = Math.round((parseInt($('graphs').getStyle('width')) - $('svg_overlay').childNodes[0].getAttribute('width').replace('px', ''))/2);
		//$('svg_overlay').style.setProperty('left',left+'px' , '');
		$('svg_overlay').style.setProperty('display', 'block','');
		$('svg_overlay').childNodes[0].setAttribute('width', $('images').getWidth());
		$('image').childNodes[0].setAttribute('width', $('images').getWidth());
		$('svg_overlay').childNodes[0].setAttribute('height', $('images').getHeight());
		$('image').childNodes[0].setAttribute('height', $('images').getHeight());

			/*
		//Tag and hide second level nodes and edges
		$H(graphviz).keys().each(function(n) { 
			if (!graphviz[n]['fromId'] && graphviz[n]['cash'] < 10000) {
				$(graphviz[n]['id']).setAttribute('class', 'node leveltwo');
				$('f'+graphviz[n]['id']).setAttribute('class', 'node leveltwo');
				$H(nodelookup[graphviz[n]['id']]['edges']).keys().each(function(e) {
					if (graphviz[e]) { 
						$(e).setAttribute('class', 'edge leveltwo');
						$('f'+e).setAttribute('class', 'edge leveltwo');
					}
				});
			}
		});
			*/
		//$('graphs').style.height = $('svg_overlay').childNodes[0].getAttribute('height');
		//$('svg_overlay').clonePosition($('image'), {'setLeft': true});
		this.setupListeners();
	},
	setupListeners: function($super) {
		$super();
		Event.observe($('svgscreen'),'click', this.Framework.unselectNode.bind(this.Framework));
		Event.observe($('svg_overlay'), 'mousemove', this.mousemove.bind(this));
		Event.observe($('graphs'), 'mousemove', this.mousemove.bind(this));
		$$('.node').each( function(n) {
			if (n.id[0] == 'f') { return; }
			var nodeid = n.id;
			var node = this.Framework.data.nodes[n.id]
			if (node['zoom']) { 
				this.addClassName($(nodeid), 'zoom_'+node['zoom']);
				this.addClassName($('f'+nodeid), 'zoom_'+node['zoom']);
			}
			if (node['class']) { 
				node['class'].split(' ').each( function(c) { 
					this.addClassName($(nodeid), c);	
					this.addClassName($('f'+nodeid), c);	
				}, this);
			}
			if (n.childNodes[1]) {
				Event.observe(n,'mouseover', function(e) { eval(node.onMouseover); }.bind(this));
				Event.observe(n,'mouseout', function(e) { this.Framework.unhighlightNode(n.id); }.bind(this));
				Event.observe(n,'click', function(e) { eval(node.onClick); }.bind(this));
			}
		}, this);
		$$('.edge').each( function(n) {
			if (n.id[0] == 'f') { return; }
			var edgeid = n.id;
			var edge = this.Framework.data.edges[edgeid];
			if (edge['zoom']) { 
				this.addClassName($(edgeid), 'zoom_'+edge['zoom']);
				this.addClassName($('f'+edgeid), 'zoom_'+edge['zoom']);
			}
			if (edge['class']) { 
				edge['class'].split(' ').each( function(c) { 
					this.addClassName($(edgeid), c);	
					this.addClassName($('f'+edgeid), c);	
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
    if (!this.hasClassName(element, className))
      elementClassName += (elementClassName ? ' ' : '') + className;
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


});
