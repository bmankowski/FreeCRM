select * from vtiger_cron_task 
;

select * from s_yf_handler_updater;


select * from vtiger_users;

select * from vtiger_crmentity;

update vtiger_crmentity set smcreatorid = 1 where smcreatorid = 6;
update vtiger_crmentity set smownerid = 1 where smownerid = 6;
update vtiger_crmentity set modifiedby = 1 where modifiedby = 6;
