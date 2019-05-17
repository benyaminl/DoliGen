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
    global $db, $user, $langs; // Panggil DB diluar
    include_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
    include_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";

    for ($j = 1; $j < $jumlah; $j++) { 
        #region Generate Data
        $query = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE price_ttc >= ".GETPOST("minharga")." AND price_ttc <= ".GETPOST("maxharga")." AND cost_price > ".GETPOST("minharga")." AND cost_price <= ".GETPOST("maxharga");
        
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
                $com->addline($taken[0]["description"], $taken[0]["price_ttc"], rand(GETPOST("stokmin"), GETPOST("stokmax")), 1, 0, 0, $taken[0]["rowid"], 0, 0, 0, 'HT', 0, '','', 0, -1, 0, 0, null, $taken[0]["cost_price"]);
                $com->addline($taken[1]["description"], $taken[1]["price_ttc"], rand(GETPOST("stokmin"), GETPOST("stokmax")), 1, 0, 0, $taken[1]["rowid"], 0, 0, 0, 'HT', 0, '','', 0, -1, 0, 0, null, $taken[0]["cost_price"]);
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

    $query = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_statut = 0";
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

function generatePO(int $jumlah) : void {
    global $db, $user, $langs;
    for ($j=1; $j < $jumlah; $j++) { 
        // Mulai dengan tarik data dari commande
        $queryBarang = "SELECT d.*, RAND(), p.cost_price FROM ".MAIN_DB_PREFIX."commande h, ".MAIN_DB_PREFIX."commandedet d, ".MAIN_DB_PREFIX."product p WHERE h.rowid = d.fk_commande AND h.fk_statut = 1 AND p.rowid = d.fk_product ORDER BY 2 LIMIT 2";
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
                $com->addline($data[0]["description"], $data[0]["cost_price"], rand($data["qty"], $data["qty"] + 5), 1, 0, 0, $data[0]["fk_product"]);
                $com->addline($data[1]["description"], $data[1]["cost_price"], rand($data["qty"], $data["qty"] + 5), 1, 0, 0, $data[1]["fk_product"]);
                $com->valid($user); // Then Validate
                $com->generateDocument('muscadet', $langs);
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
}

if (GETPOSTISSET("btnKirim")) {
    generateSO((int) GETPOST("jumlah"));
}

if (GETPOSTISSET("btnKirimPO")) {
    generatePO((int) GETPOST("jumlah"));
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