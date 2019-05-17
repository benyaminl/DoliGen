select * from llx_commande;
select rowid, ref, entity from llx_commande WHERE ref not like '(%' AND fk_statut >=2

SELECT p.rowid as idbrg, d.qty, d.subprice as harga, p.label
			FROM llx_commande h, llx_commandedet d, llx_product p
			WHERE 
				h.rowid = d.fk_commande AND d.fk_product = p.rowid; AND h.ref = '';

update llx_returngoods_returns SET status = 1 WHERE kode_retur = "(DRAFT1)";

select * from llx_element_element;

select * from llx_commande;
select * from llx_commandedet;

INSERT INTO llx_commande ( ref, fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note_private, note_public, ref_ext, ref_client, ref_int, model_pdf, fk_cond_reglement, fk_mode_reglement, fk_account, fk_availability, fk_input_reason, date_livraison, fk_delivery_address, fk_shipping_method, fk_warehouse, remise_absolue, remise_percent, fk_incoterms, location_incoterms, entity, fk_multicurrency, multicurrency_code, multicurrency_tx) VALUES ('(PROV)', 1, '2019-05-17 09:53:42', , null, '2019-03-23 07:00:00', null, '', '', null, null, null, 'einstein', null, null, NULL, null, null, '2019-03-28 07:00:00', NULL, NULL, NULL, NULL, 0, 0, '', 1, 0, 'IDR', 1);

SELECT rowid, RAND() as random FROM llx_societe WHERE fournisseur = 1 ORDER BY 2 limit 1

select d.* from llx_commandedet d, llx_commande h WHERE d.fk_commande = h.rowid;

select * from llx_commandedet;

select * from llx_commande;

select * from llx_product;

SELECT d.*, RAND(), p.cost_price FROM llx_commande h, llx_commandedet d, llx_product WHERE h.rowid = d.fk_commande AND h.fk_statut = 1 AND p.rowid = d.fk_product ORDER BY 2 LIMIT 2
SELECT rowid FROM llx_shippingschedule_schedule WHERE entity = 2  LIMIT 1
