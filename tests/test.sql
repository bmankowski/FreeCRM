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
