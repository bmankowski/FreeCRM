-- Migrate email templates from yetiforce to freecrm (32 active templates)

INSERT INTO freecrm.vtiger_crmentity (
  crmid, smcreatorid, smownerid, shownerid, modifiedby, setype, description, attention,
  createdtime, modifiedtime, viewedtime, closedtime, status, version, presence,
  deleted, was_read, private, users
)
SELECT
  ce.crmid, ce.smcreatorid, ce.smownerid, ce.shownerid, ce.modifiedby, ce.setype, ce.description, ce.attention,
  ce.createdtime, ce.modifiedtime, ce.viewedtime, NULL, ce.status, ce.version, ce.presence,
  ce.deleted, ce.was_read, ce.private, ce.users
FROM yetiforce.vtiger_crmentity ce
INNER JOIN yetiforce.u_yf_emailtemplates et ON ce.crmid = et.emailtemplatesid
WHERE ce.deleted = 0;

INSERT INTO freecrm.u_yf_emailtemplates (
  emailtemplatesid, name, number, email_template_type, module, subject, content, sys_name, email_template_priority
)
SELECT
  et.emailtemplatesid, et.name, et.number, et.email_template_type, et.module, et.subject, et.content, et.sys_name, et.email_template_priority
FROM yetiforce.u_yf_emailtemplates et
INNER JOIN yetiforce.vtiger_crmentity ce ON et.emailtemplatesid = ce.crmid
WHERE ce.deleted = 0;
