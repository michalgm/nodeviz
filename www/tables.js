var contributionstable;
var candidatestable;
var companiestable;

function showDetails(id, type, showRelations, scrollTo) {
	ctables = 'tables';
	otables = 'cotables';
	otype= 'company';
	if (type == 'company') { 
		ctables = 'cotables'; 
		otables = 'tables';
		otype= 'candidate';
	}
	infodiv = type+'Info';
	if (! $(infodiv)) {
		var div = document.createElement('p');
		div.setAttribute('id', infodiv);
		$(ctables).appendChild(div);
	}
	if (id == current[type] && ! scrollTo && qparams['type'] != 'search') { 
		$(infodiv).hide();
		if ($('t'+current[type])) { 
			$('t'+current[type]).removeClassName('selected');
		}
		current[type] = "";
		if (useSVG) { 
			hideNetwork();
		}
		return;
	}
	if (qparams['type'] == 'search') {
		infodiv = otype+'Info';
		toggleDisplay(otables, 1);
	} else if (ctables != current['view']) { 
		toggleDisplay(ctables, 1);
	}
	/*
	if (($('l'+id)) && ($('l'+id).hasClassName('selected')) && (current['congress_num'] == $('congress_num').value) && ($(infodiv).descendants().length != 0)) { 
		if (type == 'candidate') { other = 'company'; } else { other = 'candidate'; }
		if (current[other] != '') { 
			//loadDetails(current['company'], current['candidate'], type); 
		}
		return; 
	}
	*/

	loading(infodiv);
	$(infodiv).show();
	if ($('t'+current[type])) {
		$('t'+current[type]).removeClassName('selected');
		current[type] = "";
	}
	if($(infodiv).descendants().length > 4) { 
		$(infodiv).innerHTML = "";
		current[type]	= "";
		return;
	}
	if ($('t'+id)) { 
		var target = $('t'+id);
		//target.appendChild($(infodiv));
		origOffset = target.offsetTop;
		if (scrollTo) { 
			target.parentNode.scrollTop = origOffset;
		} 
		if (useSVG && current['network'] != id) { 
			showNetwork(id);
		}
		target.parentNode.scrollTop = target.parentNode.scrollTop - (origOffset - target.offsetTop) - $(type+'Sort').getHeight();
		//if (current[type] == id && $('c'+id)) { return; }
	}
	current[type] = id;
	if ($(type+'List').style.display != 'none') {
		if($('t'+id)) {
			$('t'+id).addClassName('selected');
		} else { 
			$(infodiv).innerHTML = "";
			return; 
		}
	}
	params = getFormData();
	params['table'] = 1;
	params['id'] = id;
	params['type'] = type;
	if ($('searchtype')) {
		params['candidateLimit'] = maxNodeLimit;
		params['companyLimit'] = maxNodeLimit;
	}
	requests[requests.length+1] = new Ajax.Request('request.php', {
		method: 'get',
		parameters: params,
		timeOut: timeOutLength,
		onLoading: onLoading,
		onTimeOut: timeOutExceeded,
		onComplete: function(resp) { 
			if ($('t'+current[type])) { 
				//$(type+'Info').style.marginTop = ($('t'+current[type]).positionedOffset()[1]-40)+'px';
			}
			if(!checkResponse(resp)) { return; }
			$(infodiv).update(data); 
			//inittable(type+"detailstable"); 
			if (type == 'candidate') { other = 'company'; } else { other = 'candidate'; }
			if (current[other] != '' && current[other] != null) { 
				//loadDetails(current['company'], current['candidate'], type); 
			}
			current['congress_num'] = $('congress_num').value;
			if(showRelations) {
				$(type+'detailstable').show();
			}
		},
		onFailure: catchFailure
	});
}


function loadTable(params, type) {
	var divElement = type+'List';
	var detailElement = type+'Info';
	current[type] = null;
	if ($(divElement)) { 
		$(divElement).innerHTML = "";
		loading(divElement);
	}
	if ($(detailElement)) { 
		$(detailElement).innerHTML = "";
	}
	params['table'] = 1;
	params['type'] = type;

	requests[requests.length+1] = new Ajax.Request('request.php', {
		method: 'get',
		parameters: params,
		timeOut: timeOutLength,
		onLoading: onLoading,
		onTimeOut: timeOutExceeded,
		onComplete: function(resp) { 
			if(!checkResponse(resp)) { return; }
			$(divElement).innerHTML = data; 
			//inittable(type+"Table");
			initQueryParams();

		}, 
		onFailure: catchFailure
	});
}


function loadDetails(company, candidate, type) {
	var rowid = "";
	if (type == 'candidate') { 
		rowid = 'c'+company; 
		//console.log(rowid, current['company']);
		if ($('t'+current['company'])) { $('t'+current['company']).removeClassName('selected'); }
		if ($('c'+current['company'])) { $('c'+current['company']).removeClassName('selected'); }
		current['company'] = company;
	} else { 
		rowid = 'c'+candidate; 
		if ($('t'+current['candidate'])) { $('t'+current['candidate']).removeClassName('selected'); }
		if ($('c'+current['candidate'])) { $('c'+current['candidate']).removeClassName('selected'); }
		current['candidate'] = candidate;
	}
	if ($(rowid)) { $(rowid).removeClassName('selected'); }
	showLightbox();
	if($('details')) { 
	
	//return;	
		if($('detailsrow').previous().id == rowid) { 
			if (type == 'candidate') { current['company'] = ""; } else { current['candidate'] = ""; }
			Element.remove($('detailsrow')); 
			if ($(type+'detailstable')) { 
				TableKit.Rows.stripe(type+'detailstable');
			}
			return;
		}
		Element.remove($('detailsrow')); 
	}	
	element = 'lightboxcontents';
	//currentCompany = rowid;
	if ($(rowid)) { $(rowid).addClassName('selected'); }
	loading(element);
	var params = getFormData();
	params['table'] = 1;
	params['id2'] = company;
	params['id'] = candidate;
	params['type'] = type;
	requests[requests.length+1] = new Ajax.Request('request.php', {
		method: 'get',
		parameters: params,
		timeOut: timeOutLength,
		onLoading: onLoading,
		onTimeOut: timeOutExceeded,
		onComplete: function(resp) { 
			if(!checkResponse(resp)) { return; }
			$(element).innerHTML = data;
			if (qparams['type']=='congress'){
				//if we are on the congress view, say contributions have been filtered
				$(element).innerHTML+="<div>"+$('disclaimer').innerHTML+"</div>"; 
			}
			//start table sorting
			inittable("contributionstable"); 
		}, 
		onFailure: catchFailure
	});
}

//makes tablekit sorting active
function inittable(table) { 
	//if( ! $(table) ) {console.log(table); return; }
	TableKit.options.resizable = false;
	TableKit.options.editable = false;
	if (TableKit['tables'][table]) {
		TableKit.reloadTable(table);
	} else { 
		TableKit.Sortable.init(table);
	}
}
