var GraphFramework = Class.create();

GraphFramework.prototype = {
	initialize: function(options) { 
		this.timeOutLength = 100;
		this.errordiv = 'error';
		this.lightboxdiv = 'lightbox';
		this.lightboxscreen = 'lightboxscreen';
		this.optionsform = 'graphoptions';
		this.frameworkPath = 'framework/';
		Object.extend(this, options);
		if (! this.prefix) { 

			if (typeof(graphframeworkcounter) == 'undefined') { 
				graphframeworkcounter = 1;
			} else {
				graphframeworkcounter++;
			}
			this.prefix = 'graph'+graphframeworkcounter+'_';
		}
		if (! $(this.errordiv)) { 
			$(document.body).insert({ top: new Element('div', {'id': this.errordiv}) });
		}
		this.renderers = {};
		if (this.graphdiv) { 
			if (document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1")) { 
				this.useSVG = 1;
				this.renderers['GraphImage'] = new GraphSVG(this);
			} else {
				this.useSVG = 0;
				this.renderers['GraphImage'] = new GraphRaster(this);
			}
		}
		if(this.listdiv) { 
			this.renderers['GraphList'] = new GraphList(this);
		}
		if ($(this.optionsform)) {
			Element.observe($(this.optionsform), 'change', this.reloadGraph.bind(this));
		}
		this.reloadGraph();
	},
	checkResponse: function(response) {
		var statusCode = null;
		var statusString = "Unknown response from server: ";  
		if (response.status == '200') { 
			var responseData;
			if (response.responseJSON) {
				responseData = response.responseJSON;
			} else {
				responseData = response.responseText;
			}
			if ( typeof(responseData) == 'undefined' || ! responseData.statusCode){  //NO STATUS CODE
			   this.reportError(-1, statusString+' Response was <div class="code">'+response.responseText.escapeHTML()+'</div>');
			} else if(responseData.statusCode == 1) { //EVERYTHING OK
				return responseData.data;
			} else {  //STATUS INDICATES AN ERROR
			   this.reportError(responseData.statusCode, responseData.statusString.escapeHTML());
			}
		} else {
			this.reportError(response.status, response.statusText.escapeHTML());
		}
		this.killRequests();
		return 0;	
	},
	reportError: function(code, message) {
		$(this.errordiv).update("We're sorry, an error has occured: <span class='errorstring'>"+message+"</span> (<span class='errorcode'>"+code+"</span>)");
		$(this.errordiv).show();
	},
	clearError: function() {
		$(this.errordiv).update('');
		$(this.errordiv).hide();
	},
	timeOutExceeded: function(request) {
		statusCode=10;
		statusString = "Server took too long to return data, it is either busy or there is a connection problem";
		request.abort();
		this.reportError(statusCode, statusString);
	},
	onLoading: function(div) {
		this.loading($(div));
	},
	loading: function(element) {
		$(element).innerHTML = "<span class='loading' style='display: block; text-align: center; margin: 20px; background-color: white;'><img class='loadingimage' src='images/loading.gif' alt='Loading Data' /><br /><span class='message'>Loading...</span></span>";
		$(element).show();
	},
	killRequests: function() {
		this.requests.each(function(r, index) {
			if (! r) { return; }
			if (r.transport && r.transport.readyState != 4) { 
				r.abort();
			}
			this.requests.splice(this.requests.indexOf(r), 1);
		}, this);
		$$('.loading').each(function (e) { e.remove(); });
	},
	setOffsets: function() { 
		if ($('image')) {
			this.offsetX = Position.positionedOffset($('image'))[0] ;
			this.offsetY = Position.positionedOffset($('image'))[1] ;
			this.tooltipOffsetX = Position.cumulativeOffset(this.graphdiv)[0] - 15;
			//tooltipOffsetY = Position.cumulativeOffset($('graphs'))[1];
			this.tooltipOffsetY = 5;
		}
	},
	getGraphOptions: function() {
		this.params = Form.serialize($('graphoptions'), true);
		this.params['prefix'] = this.prefix;
		$H(this.renderers).values().invoke('appendOptions');
		return this.params;
	},
	resetGraph: function(params) {
		$H(this.renderers).values().invoke('reset');
		this.current = {'zoom': 1, 'network': '', 'node': '', 'nodetype': ''};
		this.requests = [];
		this.data = [];
		this.clearError();
	},
	reloadGraph: function(params) {
		//console.time('load');
		//console.time('reset');
		this.resetGraph();
		//console.timeEnd('reset');
		
		/*	
		var eventcount = 0;
		$('graphs').descendants().each( function(e) { 
			if (Element.getStorage(e).get('prototype_event_registry') ) {
				//eventcount += Element.getStorage(e).get('prototype_event_registry').values().size();
				Element.getStorage(e).get('prototype_event_registry').values().each(function (v) { 
					if (v[0]) { 
						eventcount++;		
						console.log(e);
					} 
				});
				//console.log(Element.getStorage(e).get('prototype_event_registry'));
				//console.log(e);
			}
		});
		console.log(eventcount);
		//if (this.data) { console.log('fuck'); return; }
		*/	

		var params = this.getGraphOptions();
		//console.time('fetch');
		var request = new Ajax.Request(this.frameworkPath+'request.php', {
			parameters: params,
			timeOut: this.timeOutLength,
			onLoading: function() { this.onLoading('images'); }.bind(this),
			onTimeOut: this.timeOutExceeded.bind(this),
			evalJS: true,
			sanitizeJSON: true,
			onComplete: function(response,json) {
				//console.timeEnd('fetch');
				var responseData = this.checkResponse(response);
				if (responseData) {
					this.data = responseData.graph.data;
				//	console.time('render');
					$H(this.renderers).values().invoke('render', responseData);
					//this.renderers.GraphImage.renderGraph(data.img, data.overlay);
				//	console.timeEnd('render');
				//	console.timeEnd('load');
				}
			}.bind(this)
		});
		this.requests[this.requests.length+1] = request;
	},
	zoomToNode: function(id) {
		//zooms graph if in svg mode
		if (this.useSVG ==1) {
			this.renderers['GraphImage'].zoomToNode(id);
		}
	},
	highlightNode: function(id, noshowtooltip) {
		id = id.toString();
		if (! id) { return; }
		//if(typeof this.data.nodes[id] == 'undefined') { id = this.current['node']; }
		if (this.data.nodes[id]) {
			this.unhighlightNode(this.current['node']);
			this.current['node'] = id;
			$H(this.renderers).values().invoke('highlightNode', id, noshowtooltip);
		}
	},
	unhighlightNode: function(id) {
		if (typeof(id) == 'object') { id = this.current.node; }
		if (! id) { return; }
		id = id.toString();
		$H(this.renderers).values().invoke('unhighlightNode', id);
		this.current['node'] = '';
	},
	selectNode: function(id, noscroll) { 
		if (typeof(id) == 'object') { id = this.current.node; }
		id = id.toString();
		if (id == this.current['network']) { 
			this.unselectNode(1);
			return;
		}
		this.unselectNode();
		if(typeof this.data.nodes[id] == 'undefined') { id = this.current['node']; }
		$H(this.renderers).values().invoke('selectNode', id);
		this.current.network = id;
	},
	unselectNode: function(fade) {
		if (this.current.network == '') { return; }
		$H(this.renderers).values().invoke('unselectNode', this.current.network, fade);
		//this.highlightNode(this.current.network);
		this.current.network = '';
	},
	selectEdge: function(id) {
		var params = this.getGraphOptions();
		params.action = 'displayEdge';
		params.edgeid = id;
		var request = new Ajax.Request(this.frameworkPath+'request.php', {
			parameters: params,
			timeOut: this.timeOutLength,
			onLoading: function() { this.onLoading(this.lightboxdiv+'contents'); }.bind(this),
			onTimeOut: this.timeOutExceeded.bind(this),
			sanitizeJSON: true,
			onComplete: function(response,json) {
				data = this.checkResponse(response);
				if (data) {
					var edge = this.data.edges[id];
					var mainhead = "<h2>A Detailed View</h2>";
					var header = "<h3>From <span class='org_title'>"+this.data.nodes[edge['fromId']].Name+"</span> to <span class='org_title'>"+this.data.nodes[edge['toId']].Name+"</span></h3>";
					var subhead = "<p class='subhead'>A complete listing of the grants used to construct the edge between the organizations</p>";
					var tableheader = '<thead><tr><th>'+$H($H(data).values().first()).keys().join('</th><th>')+'</th></tr></thead>';
					var tablebody = "<tbody>";
					$H(data).values().each(function (row) { 
						tablebody += '<tr><td>'+$H(row).values().join('</td><td>')+'</td></tr>';
					});
					this.showLightbox(mainhead+subhead+header+'<table id="edge_details_table">'+tableheader+tablebody+'</tbody></table>');
				} else { 
					this.hideLightbox();
				}
			}.bind(this)
		});
		this.requests[this.requests.length+1] = request;
	},
	unselectEdge: function() {
		$(this.lightboxdiv+'contents').update();
		$(this.lightboxdiv).hide();
		$(this.lightboxscreen).hide();

	},
	showLightbox: function(contents) { 
		this.clearError();
		if (! $(this.lightboxdiv)) { 
			$(document.body).insert({ top: new Element('div', {'id': this.lightboxdiv}) });
			$(this.lightboxdiv).insert({top: new Element('img', {'id': this.lightboxdiv+'close', 'src': 'images/close.png', 'alt': 'Close', 'class': 'close'}) });
			$(this.lightboxdiv+'close').observe('click', this.hideLightbox.bind(this));
			$(this.lightboxdiv).insert({ bottom: new Element('div', {'id': this.lightboxdiv+'contents'}) });
		}
		if (! $(this.lightboxscreen)) { 
			$(document.body).insert({ top: new Element('div', {'id': this.lightboxscreen}) });
		}
		$(this.lightboxdiv+'contents').update(contents);
		$(this.lightboxdiv).show();
		$(this.lightboxscreen).show();

	},
	hideLightbox: function() {
		$(this.lightboxdiv).hide();
		$(this.lightboxscreen).hide();
	},
//
//
//
//
// Static HTML Blocks

}
