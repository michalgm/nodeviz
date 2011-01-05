<?php
require_once("../config.php");
dbwrite("delete from contributions");
#$file = fopen("$filename", "r");
print "Importing CRP data\n";

$infields = "fec_id, companyname, party, date, amount, recipient_id, candidateid, recipientname, cycle, indshort, catshort, mflocations, type, crp_key, campaignid, donorname, company, job, congress_num, racecode, ultorg, realcode";
$fields = "fec_id, if(strcmp(ultorg,''), ultorg, companyname) as companyname, party, date, amount, recipient_id, candidateid, recipientname, cycle, indshort, catshort, mflocations, type, crp_key, campaignid, donorname, company, job, congress_num, racecode, ultorg, realcode";
dbwrite("insert into contributions ($infields, sitecode) select $fields, 'oil' from crp.crp_contribs where realcode like 'e11%'");
dbwrite("insert into contributions ($infields, sitecode) select $fields, 'coal' from crp.crp_contribs where realcode = 'E1210'");
dbwrite("insert into contributions ($infields, sitecode) select distinct $fields, 'coal'  from crp.crp_contribs join av_companies on name = companyname where realcode != 'E1210' and realcode not like 'e11%'");
dbwrite("insert ignore into contributions ($infields, sitecode) select distinct $fields, 'coal'  from crp.crp_contribs join av_companies on name != companyname and name = ultorg where realcode != 'E1210' and realcode not like 'e11%'");
dbwrite("insert into contributions ($infields, sitecode) select distinct $fields, sitecode from crp.crp_contribs join procarbon_companies on crp_name = companyname where realcode != 'E1210' and realcode not like 'e11%'");
dbwrite("insert ignore into contributions ($infields, sitecode) select distinct $fields, sitecode from crp.crp_contribs join procarbon_companies on crp_name != companyname and crp_name = ultorg where realcode != 'E1210' and realcode not like 'e11%'");

print "setting up new companies\n";
dbwrite("insert into companies (name) select companyname from (select companyname from contributions group by companyname) a left join companies on companyName = name where name is null");
	
#Store id as match_id for new companies
dbwrite("update companies set match_id = id where match_id is null");

#set the companyid on contribs to the match_id of the matching company
dbwrite("update contributions join companies on companyName = name set companyid = match_id");

print "deleting contributions from companies tagged for removal\n";
dbwrite("delete from contributions where companyid = 1");

print "recoding alternate chamber race candidatids (i.e. house members running for senate)\n";
dbwrite("update contributions a join congressmembers b on recipient_id = CRPcandID and a.congress_num = b.congress_num and CandidateId != b.fec_id and a.racecode != 'P' set a.candidateid  = b.fec_id, a.racecode = b.chamber");

print "deleting non congress or prese contributions (losers)\n";
dbwrite("delete from contributions where candidateid not in (select distinct fec_id from congressmembers where fec_id is not null) and CandidateId not like 'P%'");

print "updating cached coal and oil totals\n";
#reset the cached totals
dbwrite("update companies set oil_related =0, coal_related = 0, carbon_related=0");

#update the cached amounts
dbwrite("update companies join (select sum(amount) as amount, companyid from contributions where sitecode='oil' group by companyid) b on companyid = match_id set oil_related = amount");
dbwrite("update companies join (select sum(amount) as amount, companyid from contributions where sitecode='coal' group by companyid) b on companyid = match_id set coal_related = amount");
dbwrite("update companies join (select sum(amount) as amount, companyid from contributions where sitecode='carbon' group by companyid) b on companyid = match_id set carbon_related = amount");

#update the cached amounts for only congress
dbwrite("update companies join (select sum(amount) as amount, companyid from contributions where sitecode='oil' and candidateid not like 'P%' group by companyid) b on companyid = match_id set cong_oil_total = amount");
dbwrite("update companies join (select sum(amount) as amount, companyid from contributions where sitecode='coal' and candidateid not like 'P%' group by companyid) b on companyid = match_id set cong_coal_total = amount");
dbwrite("update companies join (select sum(amount) as amount, companyid from contributions where sitecode='carbon' and candidateid not like 'P%' group by companyid) b on companyid = match_id set cong_carbon_total = amount");

//Delete contribs from companies we know are irrelevant
dbwrite("delete from contributions where companyid = 0");

print "updating cached totals\n";
dbwrite("update congressmembers set contrib_total = 0");
dbwrite("update congressmembers a join (select candidateid as fec_id, congress_num, sum(amount) as cash from contributions where candidateid is not null and candidateid like concat(racecode, '%') and CompanyID != 1 group by candidateid, congress_num ) a using (fec_id, congress_num) set a.contrib_total = cash");
dbwrite("update congressmembers set pac_total = 0");
dbwrite("update congressmembers a join (select candidateid as fec_id, congress_num, sum(amount) as cash from contributions where candidateid is not null and candidateid like concat(racecode, '%') and CompanyID != 1 and type = 'c' group by candidateid, congress_num ) a using (fec_id, congress_num) set a.pac_total = cash");
dbwrite("update congressmembers set lifetimetotal = 0");
dbwrite("update congressmembers a join (select CandidateId as fec_id, sum(amount) as cash from contributions group by CandidateId) b using (fec_id) set lifetimetotal = b.cash");
#dbwrite("update contributions a join committees b on a.CandidateId = CommitteeId and concat('20', b.yearofelection) = a.cycle set a.CandidateId = b.candidateid  where a.candidateid like 'C%' and (b.candidateid like 'P%' or b.candidateid like 'H%' or b.candidateid like 'S%')");

$res = fetchRow("select date, count(*) from contributions  group by date having count(*) > 1 order by date desc limit 1");
print "\nUpdating the date in www/oc_bottom.inc to : ".$res[0]." (".$res[1]." contributions)\n\n";

$footer = file_get_contents("../www/oc_bottom.inc");
$footer = preg_replace("/<span id=\"date\">[^<]*<\/span>/", "<span id=\"date\">$res[0]</span>", $footer);
$fp = fopen('../www/oc_bottom.inc', 'w');
fwrite($fp, $footer);
fclose($fp);

$cong_total = '\$'.fetchValue("select format(sum(amount), 0) from contributions where (racecode = 'H' or racecode = 'S')");
print "\nUpdating the congress total in www/w/index.php to : $cong_total\n\n";
$widgets = file_get_contents("../www/w/index.php");
$widgets = preg_replace("/id=\"cong_since_1999\">[^<]+</", "id=\"cong_since_1999\">$cong_total<", $widgets);
$w = fopen('../www/w/index.php', 'w');
fwrite($w, $widgets);
fclose($w);

print "Generating Filter Options...\n";
exec("cd ../ocfrontend; php generateOptions.php > ../www/optionDefaults.js");
exec("svn commit -m 'data refresh - ".date('m-d-Y')."' ../www/optionDefaults.js ../www/oc_bottom.inc ../www/w/index.php");
print "finished!\n";
exit;
dbwrite("load data local infile '$file' into table contributions fields terminated by '\t' optionally enclosed by '\"'
	(fec_id, CompanyName, party, date, amount, recipient_id, candidateid, recipientname, cycle, indshort, catshort, mflocations, type)");


