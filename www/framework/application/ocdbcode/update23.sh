

# Yes on 23
curl -o 1323890_late5k.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailLateExcel.aspx?id=1323890&session=2009&view=LATE1"
curl -o 1323890.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailContributionsReceivedExcel.aspx?id=1323890&session=2009"

curl -o 1323890_expenses.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailExpendituresMadeExcel.aspx?id=1323890&session=2009"

#No on 23
curl -o 1324059_late5k.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailLateExcel.aspx?id=1324059&session=2009&view=LATE1"
curl -o 1324059.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailContributionsReceivedExcel.aspx?id=1324059&session=2009"

load data local infile '1324059.csv' into table contributions fields terminated by "\t" enclosed by '"' lines terminated by "\r\n" ignore 1 lines (name_of_contributor,payment_type,city,state,zip,id_number,employer,occupation,amount,transaction_date,filed_date,transaction_number) SET filer_id="1324059",filer_name="311";


drop table `california`.`contributions`;
CREATE TABLE `california`.`contributions` (
  `row_id` INT  NOT NULL AUTO_INCREMENT,
  `name_of_contributor` varchar(255) ,
  `entityid` int(11) DEFAULT NULL,
  `payment_type` varchar(100) ,
  `city` varchar(100) ,
  `state` varchar(10) ,
  `zip` varchar(10) ,
  `id_number` varchar(100) ,
  `employer` varchar(255) ,
  `occupation` varchar(255) ,
  `amount` varchar(100) ,
  `transaction_type` varchar(100) ,
  `type` varchar(100) ,
  `transaction_date` varchar(10) ,
  `filed_date` varchar(10) ,
  `transaction_number` varchar(25) ,
  `filer_id` integer ,
  `filer_entityid` integer ,
  `duplicate` int  NOT NULL DEFAULT 0 , 
  PRIMARY KEY (`row_id`)
)
ENGINE = MyISAM;

load data local infile '1323890.csv' into table contributions fields terminated by "\t" enclosed by '"' lines terminated by "\r\n" ignore 1 lines (name_of_contributor,payment_type,city,state,zip,id_number,employer,occupation,amount,transaction_date,filed_date,transaction_number) SET filer_id="1323890",filer_entityid="257";

load data local infile '1323890_late5k.csv' into table contributions fields terminated by "\t" enclosed by '"' lines terminated by "\r\n" ignore 1 lines (name_of_contributor,city,state,id_number,employer,occupation,amount,transaction_type,type,transaction_date,filed_date,transaction_number) SET filer_id="1323890",filer_entityid="257";

--delete blank rows

delete from contributions where name_of_contributor = "";

--zip and and state need to be split for 5k filings

update contributions set zip=if(locate('/',state),substring(state,6),zip); 
update contributions set state=if(locate('/',state),substring(state,1,2),state);


--conver currency to numbers
update contributions set amount=round(replace(replace(amount,",",""),"$",""));

ALTER TABLE `california`.`contributions` MODIFY COLUMN `amount` INT,
 ADD INDEX amount(`amount`);
 

-


It appears that the "late and > 5k" forms overlapp with the other filings to some degree, but transaction ids are maintained? Late also includes amendements

--flag duplicaes
update contributions a join (select name_of_contributor,transaction_date,trim(substring(transaction_number,locate('-',transaction_number)+1)) short_id from contributions group by trim(substring(transaction_number,locate('-',transaction_number)+1)) having count(*) > 1) dupes on trim(substring(a.transaction_number,locate('-',a.transaction_number)+1)) = short_id and a.name_of_contributor = dupes.name_of_contributor set duplicate = 1 where a.transaction_type is not null;

drop table `california`.`expenses`;
CREATE TABLE `california`.`expenses` (
  `row_id` INT  NOT NULL AUTO_INCREMENT,
  `date` varchar(10) ,
  `payee` varchar(255) ,
  `code_entityid` INTEGER,
  `filer_entityid` INTEGER,
  `expenditure_code` varchar(100) ,
  `description` varchar(100) ,
  `amount` varchar(100) ,
  PRIMARY KEY (`row_id`)
)
ENGINE = MyISAM;

load data local infile '1323890_expenses.csv' into table expenses fields terminated by "\t" enclosed by '"' lines terminated by "\r\n" ignore 1 lines (date,payee,expenditure_code,description,amount) SET filer_entityid = 257;

update expenses set amount=round(replace(replace(amount,",",""),"$",""));

ALTER TABLE `california`.`expenses` MODIFY COLUMN `amount` INT,
 ADD INDEX amount(`amount`);
 
 --REQUIRES SOME HUMAN EDITING TO CONVERT EXPENSE CODES
 
 --make entities for expense catetores
 
 insert into entities select null,null,expenditure_code,expenditure_code,group_concat(distinct payee),sum(amount),'http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1323890&view=expenditures',null,null,null,'expense',null from expenses group by expenditure_code having sum(amount) > 1000;

update entities set match_id = entityid where match_id is null;

update expenses join entities on expenditure_code = orig_name set code_entityid = entityid
 
 
 Other notes:
 There are lots of other Valero PACs in the CA db
 http://www.nytimes.com/2010/09/21/us/politics/21money.html?_r=1&hp
 
 Adam Smith Foundation is a missouri-based 501c4, so its donors are protected from disclosure. It normally makes small in-state donations, is it being used as a disclosure shield by Big Coal? http://showmeprogress.com/diary/4430/missouris-adam-smith-foundation-gives-498000-to-repeal-californias-greenhouse-gas-law
http://www.firedupmissouri.com/roe_harris_adam_smith_foundation
http://www.latimes.com/business/la-fi-hiltzik-20100813,0,1163344.column
LA times aledges that it is a coal front group.


AMERICAN COALITION FOR CLEAN COAL ELECTRICITY
http://www.americaspower.org/Who-We-Are/
http://www.cleancoalusa.org/about-us/members



NATIONAL PETROCHEMICAL & REFINERS ASSOCIATION
membership directory has several overlapping companies:
http://www.npra.org/forms/MemberDirectory/viewMemberDirectory?reportType=regular

"National Petrochemical & Refiners Assn" PAC committee id: C00415026

exes of Valero Etc give lots to the PAC
http://www.opensecrets.org/pacs/pacgave2.php?cycle=2010&cmte=C00415026
and also lots of PAC to PAC contribs
http://www.opensecrets.org/pacs/pac2pac.php?cycle=2010&cmte=C00415026

load data local infile 'national_petrochemical_assn_members.csv' into table memberships fields terminated by "\t"  lines terminated by "\n" (member_name) SET source ="http://www.npra.org/forms/MemberDirectory/viewMemberDirectory?reportType=regular", organization_name="NATIONAL PETROCHEMICAL & REFINERS ASSOCIATION",organization_entityid=231;

load data local infile 'national_petrochemical_assn_CRP_ind_contribs.csv' into table fed_contribs fields terminated by "\t"  lines terminated by "\n" (@dummy,@dummy,donor,@dummy,amount) SET source ="http://www.opensecrets.org/pacs/pacgave2.php?cycle=2010&cmte=C00415026", recipient="NATIONAL PETROCHEMICAL & REFINERS ASSOCIATION",recipient_entityid=231;

Petroleum Marketers Association of America
http://www.opensecrets.org/pacs/pacgave2.php?cmte=C00035204&cycle=2010



CREATE TABLE `california`.`fed_contribs` (
  `row_id` INT  NOT NULL AUTO_INCREMENT,
  `donor` varchar(250) ,
  `recipient` varchar(250) ,
  `amount` int ,
  `source` varchar(250),
  PRIMARY KEY (`row_id`)
)
ENGINE = MyISAM;

CREATE TABLE `california`.`entities` (
  `entityid` INT  NOT NULL AUTO_INCREMENT,
  `orig_name` varchar(250), 
  `label` varchar(250) ,
  `notes` varchar(250) ,
  `cash` int ,
  `source` varchar(100),
  `dem_id` int,
  PRIMARY KEY (`entityid`)
)
ENGINE = MyISAM;

--creating a table of entities so we can have ids

--add any new entities to the entities table
insert into entities select null,null,name_of_contributor, name_of_contributor,"",0,"http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1323890&view=general",null,state,zip,null,null from contributions where row_id in (select row_id from contributions left join entities on name_of_contributor = orig_name where orig_name is null and duplicate = 0);

update entities set match_id = entityid where match_id is null;

update contributions b join entities a on orig_name = name_of_contributor set b.entityid = a.match_id;

insert ignore into entities select null,donor,donor,"",0,source,null from fed_contribs where donor_entityid is null

update fed_contribs join entities on orig_name = donor set donor_entityid = match_id;

update memberships join entities on member_name = orig_name set member_entityid = match_id;

--- add the clean coal members
insert into entities select null,member_name,member_name,"",null,source, null from memberships where organization_entityid = 194


--total contributions into entities
update entities join (select entityid, sum(amount) amount from (select entityid,amount from contributions where duplicate =0
union all select donor_entityid, amount from fed_contribs) edges group by entityid) totals using (entityid) set cash = amount;

-- total contributions to prop 23
update entities set cash = (select sum(amount) from contributions where filer_entityid = 257 and duplicate = 0) where entityid = 257;

--total less than 1k contributions
update entities set cash = (select sum(amount) from contributions where filer_entityid = 257 and amount < 1000) where entityid = 322;


--crude network
select entityid, concat("[label='",label,"',URL='$",cash,"'];") label from entities where cash >= 1000 or cash = 0

select fromid," -> ",toid,concat(" [penwidth=",sqrt(cash)/10,"];") from (select entityid toid,filer_name fromid,sum(amount) cash from contributions where duplicate =0 group by entityid
union select donor_entityid, recipient_entityid,amount from fed_contribs) edges order by cash desc


mysql -h192.168.2.2 -uoilchange -poilchange california -e "select entityid, concat(\"[label='\",label,\"',URL='$\",cash,\"'];\") label from entities where cash >= 1000 or cash = 0" -N | sed 's/|//g' > v2test.dot

mysql -h192.168.2.2 -uoilchange -poilchange california -e "select fromid,\" -> \",toid,concat(\" [penwidth=\",sqrt(cash)/10,\"];\") from (select entityid toid,filer_name fromid,sum(amount) cash from contributions where duplicate =0 group by entityid
union select donor_entityid, recipient_entityid,amount from fed_contribs) edges " -N | sed 's/|//g' >> v2test.dot


,image=\"../trunk/www/com_images/',image_name,'.jpg\"


# calc a total for prop 27

update entities set cash = (select sum(amount) from contributions where filer_name = 257) where entityid = 257 

Other committees
1324059
1326972
1328168
1332177
1327926
1306041
1328106
1329124
1331668

$$$ NO ON 23 - CALIFORNIANS TO STOP THE DIRTY ENERGY PROPOSITION. SPONSORED BY ENVIRONMENTAL ORGANIZATIONS AND BUSINESS FOR CLEAN ENERGY AND JOBS. 
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1324059&session=2009

CREDO VICTORY FUND AGAINST PROP 23 AND TEXAS OIL COMPANIES 
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1326972&session=2009

COMMITTEE FOR A CLEAN ENERGY FUTURE NO ON PROP 23 
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1328168&session=2009

ENVIRONMENTAL DEFENSE ACTION FUND SAY NO TO PROP 23 COMMITTEE 
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1332177&session=2009

Communities Against Proposition 23, sponsored by Ella Baker Center for Human Rights
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1327926&session=2009

$$$  No on Prop 23, Committee of the NRDC
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1306041&session=2009

Clean Economy Network, No on 23 Action Fund
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1328106&session=2009

Green Tech Action Fund California Committee - No on 23
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1329124&session=2009

California Student Public Interest Research Group - Committee to Defeat Proposition 23
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1331668&session=2009

Committee to Defeat Prop 23, sponsored by Environment California 
http://cal-access.ss.ca.gov/Campaign/Committees/Detail.aspx?id=1331668&session=2009



curl -o 1324059_late5k.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailLateExcel.aspx?id=1324059&session=2009&view=LATE1"

curl -o 1324059.csv "http://cal-access.ss.ca.gov/Campaign/Committees/DetailContributionsReceivedExcel.aspx?id=1324059&session=2009"


#copy in zipcode and state info (should do this on original insert)
update entities a join contributions b on a.entityid = b.entityid set a.state = b.state, a.zip = b.zip

#compute percent carbon related
select round(sum(if(entities.type = "oil" or entities.type = "coal" ,amount,0))/sum(amount)*100,0) from contributions join entities using (entityid) where duplicate = 0 ;

#out of state percent
select round(sum(if(!isnull(entities.state) and entities.state != "CA",amount,0))/ sum(amount)*100) from contributions join entities using (entityid) where duplicate = 0;

#oil total
select sum(amount) from contributions join entities using (entityid) where duplicate = 0 and entities.type = "oil";

#coal total
select sum(amount) from contributions join entities using (entityid) where duplicate = 0 and entities.type = "coal";

#neither total 
select sum(amount) from contributions join entities using (entityid) where duplicate = 0 and entities.type is null;


#queries to add back in for expenses
 UNION select filer_entityid fromId, code_entityid toId,amount cash, concat('expense_',row_id) as transaction_number from expenses

UNION select filer_entityid fromId, code_entityid toId,amount cash, concat('expense_',row_id) as transaction_number from expenses



Link notes (to add in later)

Valero
pollution record:http://www.crocodyl.org/wiki/valero_energy
28th worst poluter: http://www.peri.umass.edu/Toxic-100-Table.265.0.html
golf turnament: http://californiawatch.org/watchblog/fore-prop-23-donors-golf-together-5377?sf651958=1

Tesoro

coordinating other group at WPMA meeting
http://wonkroom.thinkprogress.org/2010/08/05/tesoro-powerpoint/


Western Petroeum Marketers association petroleum mebers
http://www.wpma.com/associate-member-search?q=Petroleum+Products&search_by=business_type