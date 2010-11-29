<?php 
require('../config.php');

/*import the fec data into the local db*/

query("delete from fec_contribs");
query("insert into fec_contribs (fec_id, donorname, date, amount, recipient_id, donor_id, recipientname, cycle, indshort, catshort, mflocations, type, crp_key, realcode, company, job, recipcode, occupation)
	select i.FECRecNo as fec_id, 
		d.ultorg as donorname, 
		i.Date as date,  
		Amount, 
		i.recipid as recipient_id, 
		i.filerid as donor_id, 
		r.pacshort as recipientname, 
		i.cycle as cycle, 
		industry as indshort , 
		catname as catshort, 
		microfilm as mflocations, 
		'c' as type, 
		concat(i.FECRecNo, '_10'), 
		i.realcode, 
		d.ultorg,
		'' as job,
		i.recipcode as recipcode,
		fecoccemp 
	FROM crp.pac_other10 i
	join crp.cmtes10 d on i.filerid = d.CmteID 
	join crp.cmtes10 r on i.recipid = r.cmteid
	join crp.categories b on d.primcode = b.catcode
	join entities j on fec_committee_id = i.recipid 
	where realcode != ''
	and i.type in ('24K', '24R', '24Z')");

query("insert into fec_contribs (fec_id, donorname, date, amount, recipient_id, donor_id, recipientname, cycle, indshort, catshort, mflocations, type, crp_key, realcode, company, job, recipcode, occupation)
    select i.FECTransID, 
        contrib, 
        i.Date,  
        Amount, 
        i.RecipID,
        '' as candidateid,  
        s.pacshort as FirstLastP, 
        i.cycle, 
        industry, 
        catname, 
        i.microfilm, 
        'i' as type, 
        concat(i.FECTransID, '_10'), 
        i.realcode, 
		i.emp_ef,
		i.occ_ef,
        i.recipcode, 
        i.FECOccEmp 
    FROM crp.indivs10 i 
    join crp.cmtes10 s on i.cmteid = s.cmteid 
    join crp.categories b on i.realcode = b.catcode 
    join entities j on fec_committee_id = i.recipid
	where  (i.type in ('11', '15', '15E', '15J', '22Y'))");

?>
