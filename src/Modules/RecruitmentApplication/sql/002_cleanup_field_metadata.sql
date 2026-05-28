DELETE f FROM vtiger_field f
INNER JOIN (
  SELECT fieldname, MIN(fieldid) AS keep_id
  FROM vtiger_field
  WHERE tabid = 129
  GROUP BY fieldname
  HAVING COUNT(*) > 1
) dup ON f.tabid = 129 AND f.fieldname = dup.fieldname AND f.fieldid != dup.keep_id;
