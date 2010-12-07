var currentElectionYear = '08';

var offsetX;
var offsetY;
var tooltipOffsetX;
var tooltipOffsetY;
var statusCode;
var statusString;
var currentid;
var graphviz;
var debug = 0; //debugging mode on or off
var tooltip;
var current= new Array();
var timeOutLength = 90;
var requests = [];
var useSVG = 0;
var maxNodeLimit = 250;
//test for SVG support
if (document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1")) { 
	useSVG = 1;
}
//useSVG = 0;
var nodelookup = new Hash();
var edgelookup = new Hash();
current['network'] = '';
current['candidate'] = '';  //Yikes, sometimes code uses thes globals, sometimes it uses qparams
current['company']='';	
current['congress_num']='';	
current['view'] = 'cotables';
current['edge'] = '';
var nodelist;
current['zoom'] = 1;
var zoomSlider;
var zoomlevels = 8; //number of zoom levels
var zoom_delta = 2; //factor by which to zoom by
var resetsvg;

var qparams = window.location.search.toQueryParams();

function getFormData() {
	//Element.extend($('form'));
	var data="";
	data = Form.serialize($('form'), true);
	return data;
}

//Catches mousemove events
function mousemove(e) {
	if($('tooltip').style.visibility == 'visible') { 
		var mousepos = { 'x': Event.pointerX(e), 'y': Event.pointerY(e) };
		$('tooltip').setStyle({'top': (mousepos['y']- tooltipOffsetY - $('tooltip').getHeight()) + 'px', 'left': (mousepos['x']  - tooltipOffsetX) + 'px'});
	}
}

function catchFailure(response) {
		reportError(-1, "There was a problem contacting the server:"+response.status+":"+response.statusText);
}

// look for an error code, display error if found, evaluate json in response 
function checkResponse(response) {
	var statusCode = null;
	var statusString = "Something went wrong";  
	try {   //need this incase there is giberish and eval() throws js errors
		if (eval(response.responseText)) { 
		} else { //probably means it was stopped by time out
		   reportError(statusCode,statusString);
		}
	} catch (e) {}//HEY FIXME, WHY CATCH WITHOUT PRINTING A MESSAGE
		
	if (!statusCode){  //NO STATUS CODE
	   reportError(-1, "Unknown response from server:\n\n"+response.responseText);
	} else if(statusCode == 1) { //EVERYTHING OK
		return 1;
	} else {  //STATUS INDICATES AN ERROR
	   reportError(statusCode, statusString);
	}
	return 0;	
}

function highlightNode(id, text, noshowtooltip) {
	setOffsets();
	if(typeof graphviz[id] == 'undefined') { id = currentid; }
	if (graphviz[id]) {
		var node = graphviz[id];
		currentid = id;
		//$('debug').innerHTML += node['label'];
		if (! noshowtooltip) { 
			showTooltip(text); //Set the tooltip contents
		}
		//tooltip.style.top = parseFloat(node['posy'])-10 + offsetY - tooltip.clientHeight + 'px'; //Move the tooltip, offset 10 pixels from the point
		//tooltip.style.left = parseFloat(node['posx'])+ 10 + offsetX + 'px'; 
		if (useSVG) { 
			highlightSVGnode(id);
		} else { 
			$('highlight').style.width = parseFloat(node['width']) +2 +'px';
			$('highlight').style.height = parseFloat(node['height']) +2 +'px';
			$('highlight').style.top = parseFloat(node['posy']) -1 + offsetY + 'px';
			$('highlight').style.left = parseFloat(node['posx']) -1 + offsetX + 'px';
			$('highlight').style.visibility = 'visible';
			if (node['shape'] != 'circle') { 
				$('highlight').addClassName('selected');
				$('highlightimg').style.visibility = 'hidden';
			} else {
				$('highlight').removeAttribute('class');
				$('highlightimg').style.visibility = 'visible';
			}
		}
		//$('debug').innerHTML += '('+node['posx']+' '+ offsetX;
	}
}

function showTooltip(label) {
	tooltip = $('tooltip');
	if(label) { 
		tooltip.innerHTML = label;
	}
	tooltip.style.visibility='visible'; //show the tooltip
};

function hideTooltip() { 
	$('highlight').style.visibility = 'hidden';
	$('highlightimg').style.visibility = 'hidden';
	$('tooltip').style.visibility='hidden'; //hide the tooltip first - somehow this makes it faster
	$('images').style.cursor = 'default';	
	currentid = "";
}

//Catches click events
function selectNode(id, noscroll) { 
	if (!$(id)) { return; }
	if(typeof graphviz == 'undefined' || graphviz == null ) { return; }
	if(typeof graphviz[id] == 'undefined') { id = currentid; }
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
}

function selectEdge(e) { 
	var id;
	if (useSVG) { 
		id = e.element().parentNode.id;
	} else { 
		id = e.element().id;
	}
	var nodes = new Array(graphviz[id]['fromId'], graphviz[id]['toId']);
	hideNetwork(1);
	type = 'candidate';
	if (current['view'] == 'cotables') { type = 'company'; }
	if (qparams['type'] != 'search') { 
		if (type == 'candidate' && current['candidate'] != nodes[1]) { 
			current['network'] = nodes[1];
			showDetails(nodes[1], 'candidate', 1, 1);
		} else if (type == 'company' && current['company'] != nodes[0]) { 
			current['network'] = nodes[0];
			showDetails(nodes[0], 'company', 1, 1);
		}
	}
	showEdge(id);
	loadDetails(nodes[0], nodes[1], type);
	current['network'] = '';
	return;
	var mousepos = { 'x': Event.pointerX(e), 'y': Event.pointerY(e) };
	$('info').setStyle({'top': (mousepos['y'] - tooltipOffsetY - 186) + 'px', 'left': (mousepos['x']  - tooltipOffsetX - 66) + 'px'});

	//showInfo(id);
}

function showInfo(id) {
	setOffsets();
	var node = graphviz[id];
	$('info').style.display = 'none'; 
	$('tail').style.display = 'none'; 
	$('tooltip').style.visibility = 'hidden';
	if (! node['fromId']) { 
		var width = parseFloat(node['width']);
		var diff = ((width/2)*Math.sqrt(2)) - (width/2);
		if (node['shape'] == 'box') {
			$('info').style.left = (parseFloat(node['posx']) +  offsetX - 61 + width) + 'px';
			$('info').style.top = (parseFloat(node['posy']) + offsetY- 181) + 'px';
			current['candidate'] = id;
		} else if(node['shape'] == 'circle') {
			$('info').style.left = (parseFloat(node['posx']) +  offsetX - 59 + width) - diff + 'px';
			$('info').style.top = (parseFloat(node['posy']) + offsetY- 183) + diff + 'px';
			current['company'] = id;
		}
	}
	$('tail').style.left = (parseFloat($('info').style.left) + 61) + 'px';
	$('tail').style.top = (parseFloat($('info').style.top) + 146) + 'px';
	Effect.Appear('info', {duration: .8, to: .9});
	Effect.Appear('tail', {duration: .8, to: .9});
	getInfo(id);
}

function hideInfo() {
	Effect.Fade('info', {duration: .5});
	Effect.Fade('tail', {duration: .5});
}

function getInfo(id) {
	if(typeof graphviz[id] == 'undefined') { id = currentid; }
	var node = graphviz[id];
	loading($('infocontenttext'));
	//document.getElementById('infocontenttext').innerHTML ="<p>looking up "+node['label']+"...</p>";
	var response = "lookup error";
	var params = getFormData();
	params['id'] = id;
	params['action'] = 'show'+node['type']+'Info';

	requests[requests.length+1] = new Ajax.Request('request.php', {
		method: 'get',
		parameters: params,
		onComplete: function(resp) {
			$('infocontenttext').innerHTML =  resp.responseText;
		}
	});
}

//This function runs once the page has loaded, and sets the ball rolling
function setupTooltips() {
	tooltip = document.getElementById('tooltip');
	var img = $('img0');
	Event.observe(img, 'mousemove', mousemove);
	Event.observe($('info'), 'mousemove', mousemove);
	Event.observe($('highlight'), 'mousemove', mousemove);
	Event.observe($('tooltip'), 'mousemove', mousemove);
	Event.observe($('highlight'), 'mouseover', highlightNode);
	Event.observe($('highlightimg'), 'mouseout', hideTooltip);
	Event.observe($('highlight'), 'mouseout', hideTooltip);
	Event.observe($('tooltip'), 'mouseout', hideTooltip);
	Event.observe($('highlight'), 'mousedown', selectNode);
	Event.observe(window, 'resize', setOffsets );
	window.setTimeout("setOffsets()", 250);
}

function setOffsets() {
	if ($('img0')) {
		offsetX = Position.positionedOffset($('img0'))[0] ;
		offsetY = Position.positionedOffset($('img0'))[1] ;
		tooltipOffsetX = Position.cumulativeOffset($('graphs'))[0] - 15;
		//tooltipOffsetY = Position.cumulativeOffset($('graphs'))[1];
		tooltipOffsetY = 5;
	}
}


function lookupGraph(params) {
	//$('title').innerHTML = "";
	hideTooltip();
	$('info').style.display='none';
	//if ($('images').childNodes[0]) { 
		//`$('imagescreen').clonePosition('images');
		//$('images').style.height = 'auto';
		//$('images').style.display =  'block';
		//$('graphs').style.height = $('imagescreen').style.height;
	//}
	$('imagescreen').innerHTML = "<span id='loading'><img class='loadingimage' src='images/loading.gif' alt='loading file icon' /><br /><span class='message'>Loading...</span></span>";
	Effect.Appear('imagescreen', {duration: .5, to: .9});
	img = "";
	imagemap = "";
	nodeX = 0;
	count = 0;
	params['useSVG'] = useSVG;
	if (1 || $('searchtype')) {
		params['candidateLimit'] = maxNodeLimit;
		params['companyLimit'] = maxNodeLimit;
	}
	var requesttimeout;
	requests[requests.length+1] = new Ajax.Request('request.php', {
		method: 'get', 
		parameters: params,
		timeOut: timeOutLength,
		onLoading: onLoading,
		onTimeOut: timeOutExceeded,
		onComplete: function(response,json) {
			if (requesttimeout && requesttimeout.currentlyExecuting) { requesttimeout.stop(); }
			if (checkResponse(response)) {
				//$('images').innerHTML = "";
				//$('images').update("<img id='img0' border=0 src='"+img+"' usemap='#G' />"+imagemap);
				if (useSVG) { 
					$('images').update(overlay);
					$('svg').setAttribute('id', 'img0');
					$('svgscreen').setAttribute('id', 'fsvgscreen');
					$A($('img0').getElementsByTagName('g')).each( function(g) { 
						var id = $(g).getAttribute('id');
						$(g).setAttribute('id', 'f'+id);
					});
					$('images').innerHTML += overlay;
					$('img0').show();
				} else {
					map = " usemap='#G'";
					$('images').update("<img id='img0' "+map+" border='0' src='"+img+"' />"+overlay);
				}
				setOffsets();
				setupTooltips();
				
				nodelist = new Hash();
				$H(graphviz).keys().each(function(n) { 
					if ( ! graphviz[n]['fromId'] && graphviz[n]['Name']) { 
						nodelist.set([graphviz[n]['Name']], n);
					}
				});
				
				new Autocompleter.Local('node_search', 'node_list', nodelist.keys(), {'fullSearch': true, 
					afterUpdateElement: function (t, l) {
						if (t.value && nodelist.get(t.value)) { 
							selectNode(nodelist.get(t.value));
						}
						t.value = '';
					}
				});

				if (useSVG) { 
					initSVG(framework);
				} else { 
					$('G').descendants().each(function(a) { 
						if (a.id.search('_') != -1) { 
							Event.observe($(a), 'mouseout', hideTooltip, 1); 
							Event.observe($(a), 'mousemove', mousemove);
						}
						if (graphviz[a.id]) { 
							if (graphviz[a.id].onMouseover != '') { Event.observe($(a), 'mouseover', function(eventObject) { eval(graphviz[a.id].onMouseover); }); }
							if (graphviz[a.id].onClick != '') { Event.observe($(a), 'click', function(eventObject) { eval(graphviz[a.id].onClick); }); }
						}
					});
					//$('graphs').style.height = '870px';
				}
				counts = {'Can': 0, 'Com': 0, 'com2can': 0};
				$H(graphviz).keys().each( function (e) { 
					counts[graphviz[e]['type']]++;
				});
				//if the graph has been limited to by the max, make sure the user knows
				if (counts['Can'] >= maxNodeLimit) {
					$('images').insert('<div id ="warning">Overload! We\'re only showing you the top '+maxNodeLimit+ ' politicians.</div>');
				} 
				if (counts['Com'] >= maxNodeLimit) { 
					$('images').insert('<div id ="warning">Overload! We\'re only showing you the top '+maxNodeLimit+ ' companies.</div>');
				}
				
				//deal with empty graphs  SHOULD NEVER REACH THIS BECAUSE RETUREND ERROR CODE 3
				if(graphviz.length==0){
					$('images').innerHTML = "<div class='norelationships'>Great! No Dirty Energy relationships were found to display.</div>";
				}
				Effect.Fade('imagescreen',  {duration: .5});
				$('images').style.height = 'auto';
				$('csvlink').show();
				$('disclaimer').show();
				initQueryParams();

			}
		},
		onFailure: catchFailure
	});
}

function loading(element) {
	$(element).innerHTML = "<span class='loading' style='display: block; text-align: center; margin: 20px; background-color: white;'><img class='loadingimage' src='images/loading.gif' alt='Loading Data' /><br /><span class='message'>Loading...</span></span>";
	$(element).show();
}


function loadform(firstload) {
	killRequests();
	//add listeners to all the form elements so that they will execute the load form events
	if (firstload) { 
		$('form').getElements().each( function (e) {
			e.observe("change", loadform);
		});
	}
	params = getFormData();
	//params['setupfile'] = setupfile;
	if (Prototype.Browser.WebKit == true && ! firstload && useSVG == 0) { 
		var url = updateLink();
		window.location.replace(url);
		return;
	}
	
	if (! firstload) {
		['network', 'candidate', 'company', 'congress_num', 'edge'].each(function(t) { current[t] = ''; } );
	}
	$(current['view']).show();
	lookupGraph(params);
	//loadTable(params, 'candidate');
	loadTable(params, 'company');
	loadtitle(params['congress_num']);
}

function initSearch(type) {
	$('graphcontent').hide();
	$('extras').hide();
	$('infographic').hide();
	$('action').hide();
	$('summary').hide();
	loading('searchload');
	/*Event.observe($('searchbox'), "keydown",
		function(event) {
			if (event.keyCode == 13) {
				$('candidatebutton').click();
			}		
		}); 	
	*/
	$('form').searchvalue.value = '';
	if (qparams['searchtype']) { 
		$('searchtype').value = qparams['searchtype'];
	}
	if (qparams['zip']) { 
		$('form').searchvalue.value = qparams['zip']; 
		findCandidates();
	} else if (qparams['can'] || qparams['com']) { 
		if (qparams['can']) { 
			$('searchtype').value = 'can';
		} else {
			$('searchtype').value = 'com';
		}

		//$('form').searchvalue.value = qparams['can']; 
		findCandidates(); 
	} else if (qparams['searchvalue']) {
		searchvalue = qparams['searchvalue'].replace('+', ' ');
		$('form').searchvalue.value = searchvalue;
		findCandidates(); 
	} else if (qparams['district']) {
		$('form').district.value = qparams['district']; 
		findCandidates();
	}
}

function toggleDisplay(type, noshow) {
	if (type == 'graph') { type = 'tables'; }	
	if (type != current['view']) {
		if(! $('error').visible()) { $(type).show(); }
		$(current['view']).hide();
		current['view'] = type;	
		if (type == 'cotables') { 
			if ($('viewcandidates')) { 
				$('viewcandidates').removeClassName('selected');
				$('viewcompanies').addClassName('selected');
			}
			if (! noshow) { 
				showDetails(current['company'], 'company', 1, 1);
			}
		} else {
			if ($('viewcandidates')) { 
				$('viewcompanies').removeClassName('selected');
				$('viewcandidates').addClassName('selected');
			}
			if (! noshow) { 
				showDetails(current['candidate'], 'candidate', 1, 1);
			}
		}
	}
	if($('error').visible()) { return; }
}

//this function formats the overall "summary" header for companies and politicians
function showSingleDetails(id, type, racecode) {
	loading('searchload');
	$('extras').hide();
	$('infographic').hide();
	$('action').hide();
	$('summary').hide();
	if (type == 'can') { 
		longtype='candidate';
	} else { 
		longtype='company';
	}
		
	$('graphcontent').show();
	if (longtype == 'candidate') { 
		ctables = 'cotables'; 
		$('tables').hide();
		otype = 'com';
	} else { 
		ctables = 'tables';
		$('cotables').hide();
		otype = 'can';
	}
	$(ctables).show();
	maxyear = 'total';
	if ($('l'+current[longtype])) { 
		$('l'+current[longtype]).removeClassName('selected');
	}
	['network', 'candidate', 'company', 'congress_num', 'edge'].each(function(t) { current[t] = ''; } );
	current[longtype] = id;
	//$('congress_num').setAttribute('value', maxyear);
	if (type == 'can') {
		$('racecode').setAttribute('value', racecode);
	} else if (type == 'com') { 
		$('racecode').setAttribute('value', 'C');
	}	
	/*
	params = getFormData();
	params[longtype+'ids'] = id;
	params['congress_num'] = 'total';
	if (qparams['congress_num']) { 
		params['congress_num'] = qparams['congress_num'];
	}
	lookupGraph(params);
	*/
	$(longtype+'List').style.display = 'none';
	$(longtype+'Info').setStyle({'float': 'none', 'margin': '0px auto', 'display': 'block'}); 
	$(longtype+'ids').setAttribute('value', id);
	$('congress_num').setAttribute('value', params['congress_num']);
	//showDetails(id, longtype, 1);
	$('l'+id).addClassName('selected');
	
	//Fetch the details view of the selected candidate/company
	details_params = {};
	details_params['search'] = 1;
	details_params[longtype+'id'] = id;

	congress_num = 'total';
	if (qparams['congress_num']) { 
		congress_num = qparams['congress_num'];
		delete qparams['congress_num'];
	} 
	loadData(id, congress_num, $('racecode').value, type);

	requests[requests.length+1] = new Ajax.Request('request.php', {
		method: 'get',
		parameters: details_params,
		onComplete: function(resp) { 
			checkResponse(resp);	
			$('searchload').innerHTML = '';
			$('searchload').hide();
			$('graph-title').innerHTML = candidateproperties['name'];
			document.title = "Dirty Energy Money: "+candidateproperties['name'];
			if (candidateproperties['party']) { 
				$('graph-title').innerHTML+= ' <span class="party-state '+candidateproperties['party']+'">('+candidateproperties['partystate']+')</span>';
				$('graph-subtitle').innerHTML = candidateproperties['title'];
			} else { 
				$('graph-subtitle').innerHTML = ' <span class="industry '+candidateproperties['sitecode']+'">'+candidateproperties['sitecode']+'</span>';
			}
			
			$('profileimage').setAttribute('src', candidateproperties['image']);
			$('profileimage').show();
			$('profilecash').innerHTML = '$'+candidateproperties['nicetotal'];
			$('profilecash').show();
			$('profilelinks').innerHTML=candidateproperties['profilelinks'];
			$('profilelinks').show();
			$('summary').show();
			$('extras').show();
			$('infographic').show();
			$('action').show();
			$('congresslist').innerHTML = candidateproperties['congresslist'];
			$(id+congress_num).addClassName('selected');
			//$('candidatedetails').innerHTML = resp.responseText; 
			//$(id+maxyear).addClassName('selected');
			//loadtitle('total');
		} 
	});
}

function loadData(id, congress_num, racecode, type) {
	if (type == 'can') { 
		otype = 'com'
		longtype='candidate';
		olongtype='company';
	} else { 
		otype = 'can'
		longtype='company';
		olongtype='candidates';
	}
	if(typeof racecode == 'undefined') { racecode = 'H'; }

	params = getFormData();
	params[longtype+'ids'] = id;
	params['type'] = longtype;
	params['racecode'] = racecode;
	params['congress_num'] = congress_num;
	if( $(current[longtype]+$('congress_num').getAttribute('value')) ) {
			$(current[longtype]+$('congress_num').getAttribute('value')).removeClassName('selected');
	}
	$('congress_num').setAttribute('value', congress_num);
	$('racecode').setAttribute('value', racecode);
	if ($(id+congress_num)) { 
		$(id+congress_num).addClassName('selected');
	}
	loadtitle(congress_num);
	///FIXME?
	//$(current['view']).show();
	lookupGraph(params);
	$(longtype+'List').style.display = 'none';
	$(longtype+'Info').setStyle({'float': 'none', 'margin': '0px auto', 'display': 'block'}); 
	if (qparams[otype]) { current[olongtype] = qparams[otype];  delete qparams[otype]; }
	if (qparams['v']) { 
		toggleDisplay(qparams['v']); 
		delete qparams['v']; 
	} else {
		showDetails(id, longtype);
	}	
}

function loadtitle(congressnum) {
	var subtitle="";
	var title="";
	var prop = 23;
	if ($('candidateFilterIndex').value == 1) { 
		prop = 26;
	}
	var race = $F($('form').racecode);
	var raceopts = advanced_opts[$F($('form').sitecode)][$('congress_num').value][race];
	contribamount = numberFormat(raceopts['minContribAmount'][$('contribFilterIndex').value]);	
	candidateamount = numberFormat(raceopts['minCandidateAmount'][$('candidateFilterIndex').value]);	
	companyamount = numberFormat(raceopts['minCompanyAmount'][$('companyFilterIndex').value]);	
	qparams['type'] = 'congress';
	current['congress_num'] = congressnum; //this is a hack to deal with the fact that we are passing with a global
	chamber = 'Senate';
	if (race == 'H') { chamber = 'House'; }
	title = "Who is Funding California's Proposition "+prop+"?";
	industry = sitecode == 'carbon' ? 'Dirty Energy' : sitecode;
	subtitle = "Move your mouse over the diagram to reveal funding relationships. Click on circles and lines to show more details about organizations and people who contributed to Prop "+prop+".";		
	$('graph-title').innerHTML = title;
	$('graph-subtitle').innerHTML = subtitle;
}

//ERRCODE 1 = everything ok, should not be here
//ERROR CODE 2 =  missing file on server
//ERROR CODE 3 = empty graph
function reportError(code, message){
	$$('.loading').each ( function (e) { $(e).remove(); }); //remove any loading images
	Effect.Fade('imagescreen',  {duration: .5});
	
	if (code == 3) {   //deal with empty graph
		$('images').innerHTML = "<div class='norelationships'>Great! We didn't find any Dirty Energy relationships to display for this politician.</div>";
/*
		['graphs', 'tables', 'cotables', 'help'].each(function (div) { $(div).hide(); });
		['images', 'candidateList', 'candidateInfo', 'companyList', 'companyInfo'].each(function(div) {
				if ($(div)) { $(div).innerHTML = ''; }
		});
		//$('images').innerHTML = "";
		//$('error').show();
		*/
		return;
	}
	//ALL OTHER ERROR STATES
   title = "An error occurred:";
   subtitle = message.escapeHTML()+" [Error code "+code+" ]" ;
   	$('error-title').innerHTML = title;
	$('error-text').innerHTML = subtitle;
	$('images').innerHTML = "<span id='loading'><span style='vertical-align: middle;' class='message'>Network image was not loaded</span></span>";
	$('error').show();
}



function updateOptions(readurl) {
	if (useSVG != 1 ) { 
		$(document.body).addClassName('nosvg');
	}	
	if (qparams['prop'] == '26') { qparams['candidateFilterIndex'] = 1; } //alias prop=26
	if (readurl && qparams['c']) { qparams['congress_num'] = qparams['c']; delete qparams['c']; } //This is because I goofed, and urls went out with this param, so we need to handle it
	if (readurl) {
		if (!advanced_opts[qparams['sitecode']]) { qparams['sitecode'] = 'carbon';}
		if (!advanced_opts[qparams['sitecode']][qparams['congress_num']]) { qparams['congress_num'] = $('congress_num').options[0].value;}
		//if (!advanced_opts[qparams['sitecode']][qparams['congress_num']][qparams['racecode']]) { qparams['racecode'] = 'S';}
	}
	if (readurl && qparams['congress_num']) {
		congress_num = qparams['congress_num'];
		$('congress_num').value = congress_num;
		current['congress_num'] = congress_num;
	} else { congress_num = $F('congress_num'); }
	if (readurl && qparams['racecode']) {
		race = qparams['racecode'];
		Field.setValue($('form').racecode, race);
	} else { race = $F($('form').racecode); }
	if (readurl && qparams['sitecode']) {
		sitecode = qparams['sitecode'];
		Field.setValue($('form').sitecode, sitecode);
	} else { sitecode = $F($('form').sitecode); }
	if (! readurl) { oldopts = raceopts; }
	raceopts = advanced_opts[sitecode][congress_num][race];

	labels = new Array('25', '50', '75');

	['company', 'candidate', 'contrib'].each( function(r) {
		var amount = r+'FilterIndex';
		oldValue = $(amount).value;
		if (readurl) { 
			var defaultValue = 0;
			if (qparams[amount]) {
				defaultValue = qparams[amount];
				$(amount).value = defaultValue;
			} else if ($F($('form').racecode) == 'P' && r == 'candidate') { 
				defaultValue = 2;
				$(amount).value = defaultValue;
				$(r+'_range').innerHTML = '75%';
			}
			var slider = new Control.Slider(r.toLowerCase()+'Handle', amount+'Slider', {'values': [0, 1, 2], range: $R(0,2), sliderValue: defaultValue,
				onChange: function(value) { 
					$(r+'_range').innerHTML = ((value+1)*25)+'%';
					//var newValue = raceopts[amount][value];
					$(amount).value = value;
					loadform();
					//$('submitbutton').click();
				},
				onSlide: function(value) {
					$(r+'_range').innerHTML = ((value+1)*25)+'%';
				}
			});
		}
		$(amount).value = defaultValue;
		if (! $(amount).value || $(amount).value == 'undefined') { 
			oldIndex = 0;
			if (typeof oldopts != 'undefined') { 
				oldIndex = oldValue;	
			}
			$(amount).value = oldIndex; 
		}
	});
}

// This function formats numbers by adding commas
function numberFormat(nStr){
  nStr += '';
  x = nStr.split('.');
  x1 = x[0];
  x2 = x.length > 1 ? '.' + x[1] : '';
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1))
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
  return '$'+x1 + x2;
}




function unhighlight(id) { 
	if (useSVG) { 
		unhighlightSVGnode(id);
	} else {
		hideTooltip();
	}
}

function showEdge(edgename) {
	com = graphviz[edgename]['fromId'];
	can = graphviz[edgename]['toId'];
	if (current['network']) { 
		hideNetwork(1);
	}
	if (current['edge']) { 
		hideEdge(1);
	}
	if(useSVG) {
		cannode = $(can);
		comnode = $(com);
		if ($('img0').getOpacity() != '0.3') {
			new Effect.Opacity('img0', { from: 1, to: .3, duration: .5});
		}
		showSVGElement(cannode);
		showSVGElement(comnode);
		showSVGElement(edgename);
	}
	if($('t'+current['candidate']) && current['candidate'] != can) {
		$('t'+current['candidate']).removeClassName('selected');
	}
	if($('t'+current['company']) && current['company'] != com) {
		$('t'+current['company']).removeClassName('selected');
	}
	if(qparams['type'] == 'search') { 
		if ($('c'+com)) {
			$('c'+com).addClassName('selected');
			if ($('c'+current['company'])) { $('c'+current['company']).removeClassName('selected'); }
		}
		if ($('c'+can)) {
			$('c'+can).addClassName('selected');
			if ($('c'+current['candidate'])) { $('c'+current['candidate']).removeClassName('selected'); }
		}
	}
	current['candidate'] = can;
	current['company'] = com;
	current['edge'] = edgename
}

function hideEdge(noshowimage) {
	if (useSVG && current['edge']) { 
		if(! noshowimage) { 
			new Effect.Opacity('img0', { from: .3, to: 1, duration: .3});
		}
		if( current['edge'] != null){
			nodes = edgelookup[current['edge']]['nodes'];
			hideSVGElement(current['edge']);
			hideSVGElement(nodes[0]);
			hideSVGElement(nodes[1]);
		}
	}
	current['edge'] = '';
}

function hideNetwork(noshowimage) {
	if (! useSVG) { return; }
	noshowimage = noshowimage == 1 ? 1 : 0; //cuz sometimes we get event object
	if (current['edge']) {
		hideEdge(noshowimage);
	}
	if (! noshowimage && $('img0').getOpacity() != '1') {
		new Effect.Opacity('img0', { from: .3, to: 1, duration: .3});
	}
	if (current['network']) { 
		hideSVGElement(current['network']);
		$H(nodelookup[current['network']]['edges']).keys().each(function(edge) {
			hideSVGElement(edge);
		});
		$H(nodelookup[current['network']]['lnodes']).keys().each(function(node) {
			hideSVGElement(node);
		});
	}
	current['network'] = null;
}

function sortList(type, property, reverse) { 
	 
	ltype = 'company';
	list = $(ltype+'List');
	prefix = 't';
	if (! list.firstChild) { 
		list = $(ltype+'Info');
		prefix = 'c';
	}
	var keys = $H(graphviz).keys();
	if (! reverse ) { keys = keys.reverse(); }
	keys = keys.sortBy(function(s) {
		var node = graphviz[s];
		if (node['type'] != type) { return 'ZZZZ'; }
		if (property == 'cash') { 
			value = parseInt(node[property]);
		} else { 
			value = node[property];
		}
		return value;
	});
	if (reverse) { keys = keys.reverse(); }

	odd = 'odd';
	keys.each(function (k) { 
		if (graphviz[k]['type'] != type) { return; }
		list.firstChild.appendChild($(prefix+k));
		if (odd == 'odd') { 
			$(prefix+k).addClassName('odd');
			odd = '';
		} else {
			$(prefix+k).removeClassName('odd');
			odd = 'odd';
		}
	});
	return;
}

function showLightbox() {
	Effect.Appear('lightbox', {duration: .5});
	Effect.Appear('screen', {duration: .5, to: .5});
}

function hideLightbox() {
	Effect.Fade('lightbox',{duration:.3});
	Effect.Fade('screen', {duration: .3});
}

function showSecondaryDetails(id) {
	type = 'candidate';
	if (graphviz[id]['type'] == 'Com') { 
		type = 'company';
	}
	if (qparams['type'] == 'search') { 
		selectNode(id, 1);
	} else {
		if ($('c'+current[type]+'Details')) {
			$('c'+current[type]+'Details').hide();
		}
		$('c'+id+'Details').toggle();
		current[type] = id;
	}
}

function initQueryParams() {
	if (qparams['can']) { 
		current['candidate'] = qparams['can']; 
		delete qparams['can'];
	}
	if (qparams['com']) { 
		current['company'] = qparams['com']; 
		delete qparams['com'];
	}
	if (qparams['v']) { 
		toggleDisplay(qparams['v']); 
		delete qparams['v'];
	}

	if (current['candidate'] && current['view'] == 'tables') { 
		selectNode(current['candidate']);
	} else if (current['company'] && current['view'] == 'cotables') { 
		selectNode(current['company']);
	}
}

function zoom(d) {
	previouszoom = current['zoom'];
	if (d == 'in') { 
		d = ++current['zoom'];
	} else if (d == 'out') { 
		d = --current['zoom'];
	} else if (d == 'reset') {
		current['zoom'] = 1;
		setCTM($('graph0'), resetsvg);
		return;
	}
	if (d < 0 || d > zoomlevels) { 
		return;
	}
	current['zoom'] = d;
	zoom_amount = d - previouszoom;

	var delta = 0.3333333333333333;
	var z = Math.pow(1 + zoom_delta, delta);
	var g = $("graph0");
	//var p = {'x': ($('svg').childNodes[0].getBBox().width/2), 'y':($('svg').childNodes[0].getBBox().height/2)};
	var p = root.createSVGPoint();
	p.x = 122;
	p.y = 257;
	p = p.matrixTransform(g.getCTM().inverse());
	z = Math.pow(z, zoom_amount);
	var k = root.createSVGMatrix().translate(p.x, p.y).scale(z).translate(-p.x, -p.y);
	setCTM(g, g.getCTM().multiply(k));

	if(! stateTf) { 
		stateTf = $('graph0').getCTM().inverse();
	}
	stateTf = stateTf.multiply(k.inverse());

}

function toggleProp(prop) {
	if (prop == 23) { 
		$("candidateFilterIndex").value = 0;
		$(document.body).removeClassName('prop26');	
		$(document.body).addClassName('prop23');	
	} else {
		$("candidateFilterIndex").value = 1;
		$(document.body).removeClassName('prop23');	
		$(document.body).addClassName('prop26');	
	}
	if (useSVG) { 
		zoomSlider.setValue(1);
	}
	loadform();
	return false;
}
