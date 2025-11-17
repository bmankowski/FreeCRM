/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Other/SQLTemplate.sql to edit this template
 */
/**
 * Author:  bmankowski
 * Created: 3 mar 2022
 */

SET sql_mode='TRADITIONAL';


with ect_projects as   (select project.projektyrekrutacyjneid as id, modifiedtime, replace(nazwa_projektu,'"',"'") as nazwa_projektu, cast(SUBSTRING_INDEX(number,'POT',-1) as UNSIGNED) as number, replace(miejsce_pracy,'"',"'") as miejsce_pracy, replace(tresc,'"',"'") as tresc, project_cf.id_praca_pl, upper(substr(users.user_name,1,2)) as initials
                        from yetiforce.vtiger_crmentity ent inner join yetiforce.u_yf_projektyrekrutacyjne project on (ent.crmid=project.projektyrekrutacyjneid)
                                                            inner join yetiforce.u_yf_projektyrekrutacyjnecf project_cf on (project_cf.projektyrekrutacyjneid=project.projektyrekrutacyjneid)
                                                            inner join yetiforce.vtiger_users users on (ent.smownerid=users.id)
                        where setype='ProjektyRekrutacyjne'
                        and   etap_sprzedazy = 'Aktywna'
                        and ent.deleted = false
                        )
select CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id', id, 'nazwa_projektu',nazwa_projektu, 'modifiedtime',modifiedtime,'number',number,'miejsce_pracy',miejsce_pracy,'tresc',tresc, 'id_praca_pl', id_praca_pl,'initials',initials) order by number desc),']')
from ect_projects
order by modifiedtime
;

--CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id', id, 'nazwa_projektu',nazwa_projektu, 'modifiedtime',modifiedtime,'number',number,'miejsce_pracy',miejsce_pracy,'tresc',tresc, 'id_praca_pl', id_praca_pl,'initials',initials)),']')