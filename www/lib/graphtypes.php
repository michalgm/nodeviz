<?php

$current_cong = max(array_keys($congresses));

$graphtypes = array(
	'presidential' => array(
		'title' => 'Past Presidential Races',
		'setupfile' => 'FECCanComGraph.php',
		'description' => "",
		'options' => '
					<form id="form" action="request.php">
					<fieldset>
						<legend>Election Year</legend>
						<select name="congress_num" id="congress_num" title="Show network for specific election year">
						<option value="110">2008</option>
						<option value="108">2004</option>
						<option value="106">2000</option>
						</select>
					</fieldset>
					<fieldset>
						<legend>Industry</legend>
						<input type="radio" name="sitecode" id="industry_oil" value="oil" title="Show network with only oil industry contributions" /><label for="industry_oil" title="Show network with only oil industry contributions">Oil</label>
						<input type="radio" name="sitecode" id="industry_coal" value="coal" title="Show network with only coal industry contributions"/><label for="industry_coal" title="Show network with only coal industry contributions">Coal</label>
						<input type="radio" name="sitecode" id="industry_all" value="carbon" checked="checked" title="Show network with both oil and coal industry contributions" /><label for="industry_all" title="Show network with both oil and coal industry contributions">All</label>
					</fieldset>
					'.getOptionsSelect('P').'
					</form>
				',
	
		'onload' => 'updateOptions(1); loadform(1);'
	),
	'congress' => array(
		'title' => 'Who is Funding California\'s Proposition 23?',
		'setupfile' => 'FECCanComGraph.php',
		'description' => "",
		'options' => '
					<form id="form" action="request.php">
					'.getCongressSelect().'
					<fieldset title="Choose which chamber of Congress to display a network for">
						<legend>Chamber</legend>
						<input type="radio" name="racecode" id="chamber_house" value="H" /><label for="chamber_house">House</label>
						<input type="radio" name="racecode" id="chamber_senate" value="S" checked="checked"/><label for="chamber_senate">Senate</label>
					</fieldset>
					<fieldset>
						<legend>Industry</legend>
						<input type="radio" name="sitecode" id="industry_oil" value="oil" title="Show network with only oil industry contributions" /><label for="industry_oil" title="Show network with only oil industry contributions">Oil</label>
						<input type="radio" name="sitecode" id="industry_coal" value="coal" title="Show network with only coal industry contributions" /><label for="industry_coal" title="Show network with only coal industry contributions">Coal</label>
						<input type="radio" name="sitecode" id="industry_all" value="carbon" checked="checked" title="Show network with both oil and coal industry contributions" /><label for="industry_all" title="Show network with both oil and coal industry contributions">All</label>
					</fieldset>
					'.getOptionsSelect().'
					</form>
				',
		'onload' => 'updateOptions(1); loadform(1);'
	),
	'search' => array(
		'title' => 'Search',
		'setupfile' => 'FECCanComGraph.php',
		'description' => "",
		'options' => "
			<div id='congresslist'></div>
			<form id='form' action='request.php'>
			<input id='searchvalue' name='searchvalue' type='hidden' />
			<input id='zip' type='hidden' name='zip' value=''/>
			<input id='candidateids' type='hidden' name='candidateids' value=''/>
			<input id='companyids' type='hidden' name='companyids' value=''/>
			<input id='congress_num' type='hidden' name='congress_num' value=''/>
			<input id='racecode' type='hidden' name='racecode' value=''/>
			<input id='noheader' type='hidden' name='noheader' value='1'/>
			<input id='searchtype' type='hidden' name='searchtype' value=''/>
			<input id='sitecode' type='hidden' name='sitecode' value='carbon'/>
			<input id='district' type='hidden' name='district' value=''/>
			</form>
			",
		'onload' => 'initSearch();'
	),
        'oildollars' => array(
                'title' => 'Search for Oil Dollars',
                'setupfile' => 'FECCanComGraph.php',
                'description' => "",
                'options' => "
                	<div id='search'></div>
		<div style='position: relative; width: 300px'>
		<div id='zipcodetab' class='viewtab' style='width: 8em; margin-left: 2em; background-color: #f2f2f2; border-bottom: 1px solid #f2f2f2;' onclick=\"toggleZipOptions('zipcode');\">Zip Search</div>
		<div id='nametab' class='viewtab unselectedtab' style='width: 9em; margin-left: 11em; background-color: #f2f2f2;' onclick=\"toggleZipOptions('name');\">Name Search</div>
			<div style='line-height: 18px; padding: 5px 0px; visibility: hidden;'>filler</div>
		</div>
		<div style='width: 270px; background-color:#f2f2f2; border: 1px solid #cccccc; margin: 1px 10px; text-align: center; padding: 10px 10px;'>
			<div id='zipcode'>Zip Code:<input id='zip' name='zip' size='5' /></div>
			<div id='name' style='display: none;'>Member Name: <input id='searchvalue' name='searchvalue' size='10' /></div>
			<input style='margin-top: 10px;' id='submitbutton' type='button' value='Find Oil Dollars' onclick='findDollars();'/>
			<input id='candidateids' type='hidden' name='candidateids' value=''/>
			<input id='congress_num' type='hidden' name='congress_num' value=''/>
			<input id='racecode' type='hidden' name='racecode' value=''/>
			<input id='noheader' type='hidden' name='noheader' value='1'/>
		</div>
                        ",
                'onload' => 'initDollars();'
        ),
        'votes' => array(
                'title' => 'how oil companies contrbuted to members who voted on bills',
                'setupfile' => 'voteGraphSetup.php',
                'description' => 'testing vote graph',
                'options' => "
                        <input id='congress_num' type='hidden' value='$current_cong';/>
                        <input id='submitbutton' type='button' value='Generate Network' onclick='lookupGraph(\"setupfile=voteGraphSetup.php\");'/>",
                'onload' => ''
        ),
        'committee' => array(
                'title' => 'congressional committee contributions',
                'setupfile' => 'committeeGraphSetup.php',
                'description' => 'test of committee graph',
                'options' => "  <select name='nodemode'>
                <option value = 'candidate'>Show contributions to members on a committee</option>
                <option value = 'committee'>Show contributions to committees</option>
        </select>
         
        <br />
        of the 
                <select name='racecode'>
                        <option value='S'>U.S. Senate</option>
                        <option value='H'>U.S. House</option>
                        <option value='J'>Joint</option>
                </select>
                Bill Id: <input value='' name='bill_id' />

                serving on".getCommitteesSelect('committeeid', 'S')."
                in
                ".getYearSelect('electionyear')."
                <br />
                <input name='contribFilterIndex' value=1000 size=5> 
        Minimum Contribution 
        <br /> <input name='candidateFilterIndex' value=5000 size=5> Minimum Candidate Total
        <br /> <input name='companyFilterIndex' value=5000 size=5> Minimum Company Total<br />
                        <input id='submitbutton' type='button' value='Generate Network' onclick='lookupGraph(\"setupfile=committeeGraphSetup.php\");'/>"
        ),
        'politician' => array(
			'title' => 'Members of Congress',
			'setupfile' => 'FECCanComGraph.php',
			'onload'=>''
		),
        'company' => array(
			'title' => 'Companies',
			'setupfile' => 'FECCanComGraph.php',
			'onload'=>''
		),

);

function getCongressSelect () {
        global $congresses;
        $string  = "<fieldset title='Show a network for a specific session of Congress'><legend>Congress</legend>";
        $string .= "<select name='congress_num' id='congress_num'>";
        foreach (array_keys($congresses) as $congress) { 
                $string .= "<option value='".$congress."'>".$congresses[$congress]."</option>"; 
        }
        $string .= "</select>";
        $string .= "</fieldset>";
        return $string;
}

function getYearSelect ($name) {
        return;
        $string = "<select name='$name'>";
        foreach (lookupYears() as $year) { $string .= "<option value='".$year."'>20".$year."</option>"; }
        $string .="</select>";
        return $string;
}

function getCommitteesSelect ($name, $type) {
        return;
        $string = "<select name='$name'><option value='null'>Any Committee</option>";
        foreach (lookupCommitties($type) as $committee) { $string .= "<option value='".$committee['cong_committee_id']."'>".$committee['committee_name']."</option>"; } 
        $string .="</select>";
        return $string;
}

function getOptionsSelect($racecode="") {
	$members = 'Members';
	if ($racecode == 'P') { 
		$members = 'Candidates';
	}

	$html ='
				<fieldset class="slider" title ="Adjust the slider to include more or fewer politicians in the network">
					<legend>Politicians</legend>
					<p>Top <span id="candidate_range" class="value">25%</span></p>
					<div id="candidateFilterIndexSlider" class="slider"><div id="candidateHandle" class="handle"></div></div>
				</fieldset>
				<fieldset class="slider" title="Adjust the slider to include more or fewer companies in the network">
					<legend>Companies</legend>
					<p>Top <span id="company_range" class="value">25%</span></p>
					<div id="companyFilterIndexSlider" class="slider"><div id="companyHandle" class="handle"></div></div>
				</fieldset>
				<fieldset class="slider" title="Adjust the slider to include more or fewer relationships in the network">
					<legend>Contributions</legend>
					<p>Top <span id="contrib_range" class="value ">25%</span></p>
					<div id="contribFilterIndexSlider" class="slider"><div id="contribHandle" class="handle"></div></div>
				</fieldset>
				<input id="contribFilterIndex" name="contribFilterIndex" type="hidden"/>
				<input id="candidateFilterIndex" name="candidateFilterIndex" type="hidden"/>
				<input id="companyFilterIndex" name="companyFilterIndex" type="hidden"/>
	';
	if ($racecode) { 
		$html .= "
                        <input id='racecode' name='racecode' type='hidden' value='$racecode'/>
		";	
	}
	return $html;
}

?>
