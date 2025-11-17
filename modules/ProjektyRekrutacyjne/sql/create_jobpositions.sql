/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Other/SQLTemplate.sql to edit this template
 */
/**
 * Author:  bmankowski
 * Created: 5 wrz 2023
 */
drop table if exists j1_jobpositions ;

CREATE TABLE IF NOT EXISTS j1_jobpositions (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  job_project_no INT(11) NOT NULL,
  job_position_name varchar(128) NOT NULL,
  job_remuneration_from varchar(128) ,
  job_remuneration_to varchar(128) ,
  job_remuneration_unit varchar(128) ,
  job_description text NOT NULL,
  job_location text ,
  job_workplace_for_map text ,
  job_start_date date ,
  job_end_date date ,
  job_contract_type text ,
  job_part_of_employment text , 
  job_level text ,
  job_employment_type text , 
  job_recruitment_type text ,
  job_technologies_required text,
  job_technologies_optional text,
  job_operating_systems text ,
  job_duties text ,
  job_requirements text , 
  job_requirements_optional text , 
  job_project_management_system text ,
  job_what_to_offer text ,
  job_sportcard tinyint(1)  DEFAULT '0',
  job_healthplan tinyint(1)  DEFAULT '0',
  job_lifeinsurance tinyint(1)  DEFAULT '0',
  description text ,
  state TINYINT NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;

-- insert into itc_jobpositions 

  
insert into j1_jobpositions (job_project_no,job_position_name,job_description,job_location,job_workplace_for_map,job_start_date, job_employment_type, job_remuneration_from, job_remuneration_to, job_remuneration_unit)
with ect_projects as   (select project.projektyrekrutacyjneid as id, modifiedtime, replace(nazwa_projektu,'"',"'") as nazwa_projektu, cast(SUBSTRING_INDEX(number,'POT',-1) as UNSIGNED) as number, replace(miejsce_pracy,'"',"'") as miejsce_pracy, workplace_for_map, replace(tresc,'"',"'") as tresc,  upper(substr(users.user_name,1,2)) as initials, tryb_pracy, remuneration_from, remuneration_to, remuneration_unit
                        from yetiforce.vtiger_crmentity ent inner join yetiforce.u_yf_projektyrekrutacyjne project on (ent.crmid=project.projektyrekrutacyjneid)
                                                            inner join yetiforce.u_yf_projektyrekrutacyjnecf project_cf on (project_cf.projektyrekrutacyjneid=project.projektyrekrutacyjneid)
                                                            inner join yetiforce.vtiger_users users on (ent.smownerid=users.id)
                        where setype='ProjektyRekrutacyjne'
                        and   etap_sprzedazy = 'Aktywna'
                        and ent.deleted = false 
                        )
select id, nazwa_projektu,tresc,miejsce_pracy,workplace_for_map,modifiedtime,tryb_pracy,remuneration_from, remuneration_to, remuneration_unit
from ect_projects 
order by modifiedtime  
;

 
-- select * from j2_jobpositions;
-- 
 

-- select *
--                         from yetiforce.vtiger_crmentity ent inner join yetiforce.u_yf_projektyrekrutacyjne project on (ent.crmid=project.projektyrekrutacyjneid)
--                                                             inner join yetiforce.u_yf_projektyrekrutacyjnecf project_cf on (project_cf.projektyrekrutacyjneid=project.projektyrekrutacyjneid)
--                                                             inner join yetiforce.vtiger_users users on (ent.smownerid=users.id)
--                         where setype='ProjektyRekrutacyjne'
--                         and   etap_sprzedazy = 'Aktywna'
--                         and ent.deleted = false ;
