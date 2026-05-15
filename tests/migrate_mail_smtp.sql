-- Migrate outbound SMTP and encryption settings from yetiforce to freecrm.
-- Preserves SMTP ids (1,2,4,5) for workflow references.

DELETE FROM freecrm.s_yf_mail_smtp;

INSERT INTO freecrm.s_yf_mail_smtp (
  id, mailer_type, `default`, name, host, port, username, password,
  authentication, secure, options, from_email, from_name, reply_to, individual_delivery, params
)
SELECT
  id, mailer_type, `default`, name, host, port, username, password,
  authentication, secure, options, from_email, from_name, reply_to, individual_delivery, params
FROM yetiforce.s_yf_mail_smtp;

ALTER TABLE freecrm.s_yf_mail_smtp AUTO_INCREMENT = 6;

DELETE FROM freecrm.a_yf_encryption;

INSERT INTO freecrm.a_yf_encryption (method, pass)
SELECT method, pass
FROM yetiforce.a_yf_encryption
LIMIT 1;
