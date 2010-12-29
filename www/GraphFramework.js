var GraphFramework = Class.create();

GraphFramework.prototype = {
	initialize: function(graphdiv, listdiv) { 
		this.graphdiv = graphdiv;
		this.listdiv = listdiv;
		this.timeOutLength = 100;
		if (! $('error')) { 
			$(document.body).insert({ top: new Element('div', {'id': 'error'}) });
		}
		this.graphDimensions = $(graphdiv).getDimensions();
		//if (document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1")) { 
		if (document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1")) { 
			this.useSVG = 1;
			this.GraphImage = new GraphSVG(this);
		} else {
			this.useSVG = 0;
			this.GraphImage = new GraphRaster(this);
		}
		this.GraphList = new GraphList(this);
		if ($('graphoptions')) {
			Element.observe($('graphoptions'), 'change', this.reloadGraph.bind(this));
		}
		this.reloadGraph();
	},
	checkResponse: function(response) {
		var statusCode = null;
		var statusString = "Something went wrong";  
		if (response.status == '200') { 
			try {   //need this incase there is giberish and eval() throws js errors
				if (eval(response.responseText)) { 
				} else { //probably means it was stopped by time out
					this.reportError(statusCode,statusString.escapeHTML());
					this.killRequests();
					return 0;
				}
			//} catch (e) {console.log('umm'); console.log(e.name); this.reportError(10, e.name);}//HEY FIXME, WHY CATCH WITHOUT PRINTING A MESSAGE
			} catch (e) {statusString = e.name+': '+e.message.escapeHTML()+'<br/>'; statusCode=null; }//HEY FIXME, WHY CATCH WITHOUT PRINTING A MESSAGE
				
			if (!statusCode){  //NO STATUS CODE
			   statusString = statusString ? statusString : "Unknown response from server:\n\n";
			   this.reportError(-1, statusString+response.responseText.escapeHTML());
			} else if(statusCode == 1) { //EVERYTHING OK
				return 1;
			} else {  //STATUS INDICATES AN ERROR
			   this.reportError(statusCode, statusString.escapeHTML());
			}
		} else {
			this.reportError(response.status, response.statusText.escapeHTML());
		}
		this.killRequests();
		return 0;	
	},
	reportError: function(code, message) {
		$('error').update("We're sorry, an error has occured: <span class='errorstring'>"+message+"</span> (<span class='errorcode'>"+code+"</span>)");
		$('error').show();
	},
	timeOutExceeded: function(request) {
		statusCode=10;
		statusString = "Server took too long to return data, it is either busy or there is a connection problem";
		request.abort();
		request.options.Framework.reportError(statusCode, statusString);
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
		if ($('img0')) {
			this.offsetX = Position.positionedOffset($('img0'))[0] ;
			this.offsetY = Position.positionedOffset($('img0'))[1] ;
			this.tooltipOffsetX = Position.cumulativeOffset(this.graphdiv)[0] - 15;
			//tooltipOffsetY = Position.cumulativeOffset($('graphs'))[1];
			this.tooltipOffsetY = 5;
		}
	},
	getGraphOptions: function() {
		var params = Form.serialize($('graphoptions'), true);
		params.useSVG = this.useSVG;
		params.graphWidth = this.graphDimensions.width;
		params.graphHeight = this.graphDimensions.height;
		return params;
	},
	resetGraph: function(params) {
		this.GraphImage.reset();
		this.GraphList.reset();
		this.current = {'zoom': 1, 'network': '', 'node': '', 'nodetype': ''};
		this.requests = [];
		this.data = [];
		$('error').update('');
		$('error').hide();
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
		var request = new Ajax.Request('request.php', {
			parameters: params,
			timeOut: this.timeOutLength,
			onLoading: function() { this.onLoading('images'); }.bind(this),
			onTimeOut: this.timeOutExceeded.bind(this),
			onComplete: function(response,json) {
				//console.timeEnd('fetch');
				if (this.checkResponse(response)) {
					this.data = graph.data;
				//	console.time('render');
					this.GraphImage.renderGraph(img, overlay);
				//	console.timeEnd('render');
				//	console.time('lists');
					this.GraphList.renderLists();
				//	console.timeEnd('lists');
					delete graph;	
					delete img;
					delete overlay;
					//this.setOffsets();
				//	console.timeEnd('load');
				}
			}.bind(this)
		});
		this.requests[this.requests.length+1] = request;
	},
	highlightNode: function(id, noshowtooltip) {
		id = id.toString();
		if (! id) { return; }
		//if(typeof this.data.nodes[id] == 'undefined') { id = this.current['node']; }
		if (this.data.nodes[id]) {
			this.unhighlightNode(this.current['node']);
			this.current['node'] = id;
			this.GraphImage.highlightNode(id, noshowtooltip);
			this.GraphList.highlightNode(id);
		}
	},
	unhighlightNode: function(id) {
		if (typeof(id) == 'object') { id = this.current.node; }
		if (! id) { return; }
		id = id.toString();
		this.GraphImage.unhighlightNode(id);
		this.GraphList.unhighlightNode(id);
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
		if (!$(id)) { return; }
		if(typeof this.data.nodes[id] == 'undefined') { id = this.current['node']; }
		this.GraphImage.selectNode(id);
		this.GraphList.selectNode(id);
		this.current.network = id;
		/*
		if (
			(id == current['candidate'] || id == current['company']) && 
			(
				(useSVG != 1 && $('info').style.display != 'none') || 
				(useSVG == 1 && current['network'] == id)
			)
		) {
			//hideInfo();
			if (useSVG) { 
				hideNetwork(id);
				return;
			}
		} else { 
			//showInfo(id);
			if (useSVG) { 
				showNetwork(id);
			}
		}
		type = 'candidate';
		if (graphviz[id]['type'] == 'Com') { 
			type = 'company';
		}
		if (qparams['type'] == 'search') { 
			ctables = 'tables';
			if (type == 'company') { 
				ctables = 'cotables'; 
			}
			if ($('c'+current[type])) {
				$('c'+current[type]).removeClassName('selected');
				if (current[type] != id) { 
					//$('c'+current[type]+'Details').hide();
				}
			}
			if ($('c'+id)) {
				elem = $('c'+id);
				elem.addClassName('selected');
				new Effect.Highlight(elem, { startcolor: '#9933ff', afterFinish: function(e){ $(e.element.id).setAttribute('style', ''); }});
				if (! noscroll) { 
					elem.parentNode.scrollTop = elem.offsetTop - $(type+'Sort').getHeight();
				}
			}
			//$('c'+id+'Details').toggle();
			current[type] = id;
		} else {
			showDetails(id, type, 0, 1);
		}
		*/
	},
	unselectNode: function(fade) {
		if (this.current.network == '') { return; }
		this.GraphImage.unselectNode(this.current.network, fade);
		this.GraphList.unselectNode(this.current.network, fade);
		//this.highlightNode(this.current.network);
		this.current.network = '';
	},
	selectEdge: function(id) {
		if (! $('edgeview')) { 
			$(document.body).insert({ top: new Element('div', {'id': 'edgeview'}) });
		}
		var params = this.getGraphOptions();
		params.action = 'displayEdge';
		params.edgeid = id;
		var request = new Ajax.Request('request.php', {
			parameters: params,
			timeOut: this.timeOutLength,
			onLoading: function() { this.onLoading('edgeview'); }.bind(this),
			onTimeOut: this.timeOutExceeded.bind(this),
			onComplete: function(response,json) {
				if (this.checkResponse(response)) {
					var edgelist = "";
					$H(data).keys().each(function (key) { 
						var edge = data[key];
						edgelist+= '<tr><td>'+[edge.CompanyName, edge.recipientname, edge.date, edge.amount].join('</td><td>')+'</td></tr>';
					});
					$('edgeview').update('<table>'+edgelist+'</table>');
				} else { 
					$('edgeview').hide();
				}
			}.bind(this)
		});
		this.requests[this.requests.length+1] = request;
	},
	unselectEdge: function() {
	}
//
//
//
//
// Static HTML Blocks

}
