select * from vtiger_cron_task 
;

select * from s_yf_handler_updater;


select * from vtiger_users;

select * from vtiger_crmentity;

update vtiger_crmentity set smcreatorid = 1 where smcreatorid = 6;
update vtiger_crmentity set smownerid = 1 where smownerid = 6;
update vtiger_crmentity set modifiedby = 1 where modifiedby = 6;




select *
from u_yf_documenttemplates
order by documenttemplatesid desc;



select * from freecrm.vtiger_zrodlo_aplikacji
;

describe  yetiforce.vtiger_zrodlo_aplikacji;

select * from freecrm.vtiger_users;

update freecrm.vtiger_users set is_admin = 'on', job_title = 'CTO' where id = 1;

select *
from u_yf_documenttemplates;

select * from vtiger_userscf;

select * from u_yf_emailtemplates;

select * from u_yf_documenttemplates;


delete from
 vtiger_settings_blocks
 where label = 'LBL_TEMPLATE';


SELECT * FROM s_yf_delayed_email_queue;


select * from vtiger_field where fieldname = 'accountname';

SELECT typeofdata, COUNT(*) as cnt FROM vtiger_field GROUP BY typeofdata ORDER BY cnt DESC LIMIT 400;

SELECT uitype, GROUP_CONCAT(DISTINCT typeofdata ORDER BY typeofdata) AS typeofdata_values, COUNT(DISTINCT typeofdata) AS distinct_count, COUNT(*) AS field_count FROM vtiger_field WHERE typeofdata IS NOT NULL AND typeofdata != '' GROUP BY uitype HAVING distinct_count > 1 ORDER BY uitype;


select * from vtiger_def_org_field;


select * from vtiger_tab_sharing_default;

select * from vtiger_field;

select * from vtiger_org_share_action_mapping;

select tabid, count(*) from vtiger_tab_sharing_default group by tabid having count(*) > 1;


select distinct vtiger_field.presence from vtiger_field;


SELECT
    vtiger_crmentity.crmid,
    u_yf_kandydaci.kandydaciid AS id,
    u_yf_kandydaci.name,
    rel.recruitment_status_rel,
    rel.comment_rel,
    rel.rel_created_time,
    rel.rel_created_user
FROM vtiger_crmentity
INNER JOIN u_yf_kandydaci
    ON u_yf_kandydaci.kandydaciid = vtiger_crmentity.crmid
INNER JOIN u_yf_projekty_rekrutacyjne_relations_members_entity rel
    ON (rel.relcrmid = vtiger_crmentity.crmid OR rel.crmid = vtiger_crmentity.crmid)
WHERE vtiger_crmentity.deleted = 0
  AND vtiger_crmentity.setype = 'Kandydaci'
  AND (rel.crmid = 1349638 OR rel.relcrmid = 1349638);

  SELECT
    c.crmid,
    k.kandydaciid AS id,
    k.name,
    rel.recruitment_status_rel,
    rel.comment_rel,
    rel.rel_created_time,
    rel.rel_created_user
FROM u_yf_projekty_rekrutacyjne_relations_members_entity rel
INNER JOIN vtiger_crmentity c
    ON c.crmid = rel.relcrmid
INNER JOIN u_yf_kandydaci k
    ON k.kandydaciid = c.crmid
WHERE rel.crmid = 1349638
  AND c.deleted = 0
  AND c.setype = 'Kandydaci';

-- One-time normalization for relation direction:
-- canonical: crmid = ProjektyRekrutacyjne, relcrmid = Kandydaci
SELECT
    SUM(CASE WHEN p1.projektyrekrutacyjneid IS NOT NULL AND k1.kandydaciid IS NOT NULL THEN 1 ELSE 0 END) AS canonical_rows,
    SUM(CASE WHEN p2.projektyrekrutacyjneid IS NOT NULL AND k2.kandydaciid IS NOT NULL THEN 1 ELSE 0 END) AS reversed_rows,
    COUNT(*) AS total_rows
FROM u_yf_projekty_rekrutacyjne_relations_members_entity r
LEFT JOIN u_yf_projektyrekrutacyjne p1 ON p1.projektyrekrutacyjneid = r.crmid
LEFT JOIN u_yf_kandydaci k1 ON k1.kandydaciid = r.relcrmid
LEFT JOIN u_yf_projektyrekrutacyjne p2 ON p2.projektyrekrutacyjneid = r.relcrmid
LEFT JOIN u_yf_kandydaci k2 ON k2.kandydaciid = r.crmid;

-- Step 1: flip only reversed rows without colliding with already existing canonical pair.
UPDATE u_yf_projekty_rekrutacyjne_relations_members_entity r
JOIN u_yf_kandydaci k ON k.kandydaciid = r.crmid
JOIN u_yf_projektyrekrutacyjne p ON p.projektyrekrutacyjneid = r.relcrmid
LEFT JOIN u_yf_projekty_rekrutacyjne_relations_members_entity canon
  ON canon.crmid = r.relcrmid
 AND canon.relcrmid = r.crmid
SET r.crmid = -r.crmid,
    r.relcrmid = -r.relcrmid
WHERE canon.crmid IS NULL;

-- Step 2: restore sign (now rows are canonical).
UPDATE u_yf_projekty_rekrutacyjne_relations_members_entity
SET crmid = -crmid,
    relcrmid = -relcrmid
WHERE crmid < 0;

-- Verify after normalization.
SELECT
    SUM(CASE WHEN p1.projektyrekrutacyjneid IS NOT NULL AND k1.kandydaciid IS NOT NULL THEN 1 ELSE 0 END) AS canonical_rows,
    SUM(CASE WHEN p2.projektyrekrutacyjneid IS NOT NULL AND k2.kandydaciid IS NOT NULL THEN 1 ELSE 0 END) AS reversed_rows,
    COUNT(*) AS total_rows
FROM u_yf_projekty_rekrutacyjne_relations_members_entity r
LEFT JOIN u_yf_projektyrekrutacyjne p1 ON p1.projektyrekrutacyjneid = r.crmid
LEFT JOIN u_yf_kandydaci k1 ON k1.kandydaciid = r.relcrmid
LEFT JOIN u_yf_projektyrekrutacyjne p2 ON p2.projektyrekrutacyjneid = r.relcrmid
LEFT JOIN u_yf_kandydaci k2 ON k2.kandydaciid = r.crmid;