<?php
/* Create using Module Builder By :
 * Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * 
 * Author of this file
 * Copyright (C) 2019      Benyamin Limanto <me@benyamin.xyz>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/returngoods/template/returngoodsindex.php
 *  \ingroup    returngoods
 *  \brief      Home page of returngoods top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include(substr($tmp, 0, ($i + 1)) . "/main.inc.php");
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php");
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include("../main.inc.php");
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include("../../main.inc.php");
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include("../../../main.inc.php");
}
if (!$res) {
    die("Include of main fails");
}
$langs->loadLangs(array('admin','orders','sendings','companies','bills','propal','supplier_proposal','deliveries','products','stocks','productbatch'));

function generateSO(int $jumlah) : void {
    global $db, $user, $langs, $conf; // Panggil DB diluar
    include_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
    include_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";

    for ($j = 0; $j < $jumlah; $j++) { 
        #region Generate Data
        $query = "SELECT  rand(),p.* FROM ".MAIN_DB_PREFIX."product p WHERE price_ttc >= ".GETPOST("minharga")." AND price_ttc <= ".GETPOST("maxharga")." AND cost_price > ".GETPOST("minharga")." AND cost_price <= ".GETPOST("maxharga")." ORDER By 1 limit 2"; // Random take item
        
        /** @var DoliDB $db */
        $res = $db->query($query);
        
        if ($res) {
            $data = [];
            $i = 0;

            // Ambil data 3rd party
            $queryThirdparty = "SELECT rowid, RAND() as random FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 0 ORDER BY 2 limit 1";
            $resThird = $db->query($queryThirdparty);
            if (!$resThird) {
                echo "fetch thirdparty error";
                return;
            }
            $id = $db->fetch_array($resThird);
            
            // data barang di tampung
            while ($i < $db->num_rows($res)) {
                $data[] = $db->fetch_array($res);
                $i++;
            }

            $taken = [];
            $pick = array_rand($data,2);
            $taken[] = $data[$pick[0]];
            $taken[] = $data[$pick[1]];

            $time = mt_rand(1549116018, time()); // Random time
            $db->begin(); // Start Transaction
            $com = new Commande($db);
            $com->socid = $id[0]; // Ambil thirdparty data
            $com->date = date('Y-m-d',$time);
            $com->date_commande = date('Y-m-d', $time);
            $shipment = new DateTime(date('Y-m-d', $time));
            $shipment->add(new DateInterval("P5D"));
            $com->date_livraison = $shipment->format("Y-m-d");
            $com->cond_reglement_id = 2; // term payment
            $com->mode_reglement_id = 2; // Cara Bayar
            $com->modelpdf = "einstein";
            $result = $com->create($user);
            
            if ($result) {
                $com->addline($taken[0]["description"], $taken[0]["price_ttc"], rand(GETPOST("stokmin"), GETPOST("stokmax")), 0, 0, 0, $taken[0]["rowid"], 0, 0, 0, 'HT', 0, '','', 0, -1, 0, 0, null, $taken[0]["cost_price"]);
                $com->addline($taken[1]["description"], $taken[1]["price_ttc"], rand(GETPOST("stokmin"), GETPOST("stokmax")), 0, 0, 0, $taken[1]["rowid"], 0, 0, 0, 'HT', 0, '','', 0, -1, 0, 0, null, $taken[0]["cost_price"]);
                $db->commit();
                echo "Berhasil - ".$j;
            } else {
                $db->rollback();
                echo "gagal";
            }
        } else {
            echo "Error query";
        }
        #endregion       
    }

    $query = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_statut = 0 AND entity = ".$conf->entity;
    $result = $db->query($query);

    if ($query) {
        $i = 0;
        while ($i < $db->num_rows($result)) {
            $row = $db->fetch_array($result);
            $com->fetch($row[0]);
            $com->valid($user);
            $i++;
        }
    }
}
include_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php"; // class founr commande
include_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php";

function generatePO(int $jumlah) : void {
    global $db, $user, $langs, $conf;
    for ($j=0; $j < $jumlah; $j++) { 
        // Mulai dengan tarik data dari commande
        $queryBarang = "SELECT RAND(),d.*, p.cost_price FROM ".MAIN_DB_PREFIX."commande h, ".MAIN_DB_PREFIX."commandedet d, ".MAIN_DB_PREFIX."product p WHERE h.rowid = d.fk_commande AND h.fk_statut = 1 AND p.rowid = d.fk_product ORDER BY 1 LIMIT 2";
        $com = new CommandeFournisseur($db);
        /** @var DoliDB $db */
        $result = $db->query($queryBarang);
        if ($result) {
            // Thirdparty
            $queryThirdparty = "SELECT rowid, RAND() as random FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 1 ORDER BY 2 limit 1";
            $resThird = $db->query($queryThirdparty);
            if (!$resThird) {
                echo "fetch thirdparty error";
                return;
            }

            $i = 0; $data = [];
            // data barang di tampung
            while ($i < $db->num_rows($result)) {
                $data[] = $db->fetch_array($result);
                $i++;
            }

            $id = $db->fetch_array($resThird);
            $time = mt_rand(1549116018, time()); // Random time
            $com->socid = $id[0]; // isi data thirdpary nya
            $com->date = date('Y-m-d',$time);
            $com->date_commande = date('Y-m-d', $time);
            $shipment = new DateTime(date('Y-m-d', $time));
            $shipment->add(new DateInterval("P5D"));
            $com->date_livraison = $shipment->format("Y-m-d");
            $com->modelpdf = "muscadet";
            $com->cond_reglement_id = 2; // term payment
            $com->mode_reglement_id = 2; // Cara Bayar
            $result = $com->create($user);

            if ($result) {
                $com->addline($data[0]["description"], $data[0]["cost_price"], rand($data["qty"], $data["qty"] + 5), 0, 0, 0, $data[0]["fk_product"]);
                $com->addline($data[1]["description"], $data[1]["cost_price"], rand($data["qty"], $data["qty"] + 5), 0, 0, 0, $data[1]["fk_product"]);
                $db->commit();
                echo "Berhasil - ".$j;
            } else {
                $db->rollback();
                echo "gagal";
            }
        } else {
            echo "Select Gagal!";
        }
    }
    
    $query = "SELECT * FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE fk_statut = 0 AND entity = ".$conf->entity;
    $result = $db->query($query);

    if ($query) {
        $i = 0;
        while ($i < $db->num_rows($result)) {
            $row = $db->fetch_array($result);
            $com->fetch($row[0]);
            $com->valid($user); // Then Validate
            echo $com->date_commande;
            $com->generateDocument('', $langs);
            $i++;
        }
    }
}

function generateInvoice(int $jumlah, string $type = 'SO',bool $pay = false) : void {
    
    include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
    include_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
    include_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php";
    global $db, $user, $conf;
    
    if ($type == 'SO') {
        $table = 'llx_commande';
        $source = "commande";
        $target = "facture";
        $status = 3;
    } else {
        $table = 'llx_commande_fournisseur';
        $source = 'order_supplier';
        $target = 'invoice_supplier';
        $status = 1;
    }

    /** @var DoliDB $db */
    $query = "SELECT rowid, rand() FROM ".$table." WHERE fk_statut = $status and rowid not in (
        select fk_source from llx_element_element where sourcetype = '$source' and targettype = '$target'
    ) and entity = ".$conf->entity." ORDER by 2"; // select query from so or po that already created 
    $result = $db->query($query);
    
    if ($db->num_rows($result) == 0) {
        echo "Tidak ada data yang bisa di proses";
        return;
    }
    
    if ($result) {
        $j = 0;
        $rows = $db->num_rows($result);
        $dataSOPO = $result; // Save check point data
        // Iterate check apakah ada invoice, kalau ga ada bikin
        while ($j < $rows && $j < $jumlah) {
            $row = $db->fetch_array($dataSOPO); 
            // Get the link between commande and invoice, is there any
            $query = "SELECT * FROM llx_element_element WHERE sourcetype = '".$source."' AND targettype = '".$target."' AND fk_source = ". $row["rowid"];
            $elRes = $db->query($query);

            if ($type == 'SO') {
                $com = new Commande($db); // Create the SO object and fetch
                $inv = new Facture($db); // Create facture object
            } else {
                $com = new CommandeFournisseur($db); // Create the supplier invoice
                $inv = new FactureFournisseur($db);
            }

            $db->begin();
            $com->fetch($row["rowid"]); // 
            if ($type == 'SO') {
                $resultInvoice = $inv->createFromOrder($com, $user); // Pass the order to be processed by the facture
            } else {
                $com->fetch_thirdparty();

                if ($com->statut == 1) {
                    $com->approve($user);
                } 

                if ($com->statut == 2) {
                    $com->commande($user, $com->date, 4);
                }

                $date = new DateTime(date('Y-m-d',$com->date_livraison));
                $date->add(new DateInterval("P".rand(1,5)."D"));
                $inv->date = $date->format("Y-m-d");
                $inv->date_echeance     = $date->format("Y-m-d");
                $inv->total_ht          = $com->total_ht;
				$inv->ref_supplier		= $com->thirdparty->ref.str_pad(rand(0,1000),5,"0", STR_PAD_LEFT);
				$inv->socid				= $com->socid;
				$inv->libelle			= "Invoice for ".$com->ref;
				$inv->cond_reglement_id	= 2;
                $inv->mode_reglement_id	= 2;
                $inv->origin            = "order_supplier";
                $inv->origin_id         = $com->id;
				$inv->fk_account		= -1;
                $resultInvoice = $inv->create($user);

                // Add Lines
                $com->fetch_lines();
                $lines = $com->lines;
                $num=count($lines);
                for ($i = 0; $i < $num; $i++) // TODO handle subprice < 0
                {
                    $desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
                    $product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);

                    // Extrafields
                    if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
                        $lines[$i]->fetch_optionals($lines[$i]->rowid);
                    }

                    // Dates
                    // TODO mutualiser
                    $date_start=$lines[$i]->date_debut_prevue;
                    if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
                    if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
                    $date_end=$lines[$i]->date_fin_prevue;
                    if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
                    if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;

                    // FIXME Missing special_code  into addline and updateline methods
                    $inv->special_code = $lines[$i]->special_code;
                    
                    // FIXME Missing $lines[$i]->ref_supplier and $lines[$i]->label into addline and updateline methods. They are filled when coming from order for example.
                    $result = $inv->addline(
                        $desc,
                        $lines[$i]->subprice,
                        $lines[$i]->tva_tx,
                        $lines[$i]->localtax1_tx,
                        $lines[$i]->localtax2_tx,
                        $lines[$i]->qty,
                        $lines[$i]->fk_product,
                        $lines[$i]->remise_percent,
                        $date_start,
                        $date_end,
                        0,
                        $lines[$i]->info_bits,
                        'HT',
                        $product_type,
                        $lines[$i]->rang,
                        0,
                        $lines[$i]->array_options,
                        $lines[$i]->fk_unit,
                        $lines[$i]->id
                    );

                    if ($result < 0)
                    {
                        break;
                    }
                }

                // Now reload line
                $inv->fetch_lines();
            }
            

            if ($resultInvoice) {
                echo "Invoice Created! - ".$com->getNomUrl(1)." -" ;

                if ($pay) { // Check does it need to be paid ?
                    $elRes = $db->query($query); // fetch the data again
                    if ($elRes) { // if it's working then
                        if ($db->num_rows($elRes) > 0) { // Check the data
                            $id = $db->fetch_array($elRes);
                            $id = $id["fk_target"];
                            $inv->fetch($id);
                            $status = $inv->validate($user); // Validate first

                            if ($status) { // After validate, do the payment
                                include_once DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php"; // Do the payment
                                include_once DOL_DOCUMENT_ROOT."/fourn/class/paiementfourn.class.php"; // Do the payment
                                $pay = new Paiement($db);
                                if ($type != 'SO') {
                                    $pay = new PaiementFourn($db);
                                }
                                // Get the code from ID
                                $pay->paiementid = dol_getIdFromCode($db,'VIR','c_paiement','code','id',1);

                                $date = new DateTime(date('Y-m-d',$com->date_livraison));
                                $date->add(new DateInterval("P".rand(1,5)."D"));
                                
                                // Put the payment date
                                $pay->datepaye = $date->format("Y-m-d");
                                $pay->multicurrency_amounts = [];
                                $com->fetch_thirdparty();
                                $pay->note = "Terima pembayaran dari ".$com->thirdparty->name;
                                
                                $pay->amounts = [ $id => $com->total_ttc];
                                $paymentResult = $pay->create($user, 1);

                                // check if success then
                                if ($paymentResult > 0) {
                                    // put to bank
                                    $tipePembayaran = "payment";
                                    $arah = "Customer";
                                    if ($type != "SO") {
                                        $tipePembayaran = "payment_supplier";
                                        $arah = "Supplier";
                                    }
                                    $resPay = $pay->addPaymentToBank($user, $tipePembayaran, $arah.' Payment', GETPOST("accid"), $com->thirdparty->name, '');
                                    if ($resPay > 0) {
                                        $db->commit();
                                        $com->classifyBilled($user); // Set it's billed
                                        if ($type != "SO") {
                                            dispatch($com);   
                                        }
                                        echo "berhasil payment dan tutup <br/>";
                                    } else {
                                        $db->rollback();
                                        echo "gagal bayar ke bank <br/>";
                                    }
                                } else {
                                    echo "gagal bayar invoice <br/>";
                                }
                            } else {
                                echo " gagal validate <br/>";
                            }
                        }
                    }
                } else {
                    $db->commit();
                }
            } else {
                echo "Broken, not working! <br/>";
            }
            $j++;
        }  
    } else {
        var_dump($db->lasterror());
    }

}
/**
 * Dispatch to Warehouse
 *
 * @param CommandeFournisseur $com
 * @return void
 */
function dispatch($com) {
    global $user;
    $com->Livraison($user, $com->date_livraison, 'tot', 'All item recieved');
    $com->fetch_lines();
    $lines =$com->lines;
    foreach ($lines as $key => $value) {
        $com->dispatchProduct($user, $value->fk_product, $value->qty, GETPOST("whid"), $value->subprice);
    }
    echo "All Item dispatched to respected warehouse!";
}

if (GETPOSTISSET("btnKirim")) {
    generateSO((int) GETPOST("jumlah"));
}

if (GETPOSTISSET("btnKirimPO")) {
    generatePO((int) GETPOST("jumlah"));
}

if (GETPOSTISSET("btnBikinINV")) {
    $pay = GETPOST("pay") == '1' ? true: false;
    generateInvoice((int) GETPOST("jumlah"), GETPOST("btnBikinINV"), $pay);
}
?>

<form method="post">
    <h1>Bikin SO</h1>
    <input type=text name=minharga placeholder=minharga><br/>
    <input type=text name=maxharga placeholder=maxharga><br/>
    <input type=text name=stokmin placeholder=stokmin><br/>
    <input type=text name=stokmax placeholder=stokmax><br/>
    <input type=text name=jumlah placeholder="jumlah generate"></br>
    <button type=submit name=btnKirim value=1>Kirim</button>
</form>

<form method=post>
    <h1>Bikin PO</h1>
    <input type=text name=jumlah placeholder="jumlah generate"></br>
    <button type=submit name=btnKirimPO value=1>Generate dari SO</button>
</form>

<form method=post>
    <h1>Bikin INV</h1>
    <input type=text name=jumlah placeholder="jumlah generate"></br>
    <input type="checkbox" name="pay" value="1"> Payment Directly <br/>
    Bank Code : <input type=text name=accid placeholder="kode bank" value=1></br>
    WH Code  : <input type=text name=whid placeholder="kode warehouse"></br>
    <button type=submit name=btnBikinINV value=SO>Generate dari SO</button>
    <button type=submit name=btnBikinINV value=PO>Generate dari PO</button>
</form>