<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage dlbuploaduser
 * @modifier   2012 Ulrich Weber
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once('locallib.php');
require_once($CFG->libdir.'/pdflib.php');

$PAGE->set_url('/admin/tool/dlbuploaduser/exportpdf.php');

$filename = clean_filename($SESSION->institution . '_users-' . gmdate("Ymd_Hi") . ".pdf");

$pdf = new pdf();

$pdf->addPage();

$col2 = 0;
$x0 = $pdf->getX();
$x = $x0;

// do not return first entry (title)
for ($i=1; $i<count($SESSION->users_inserted); $i++)
{
	$user = $SESSION->users_inserted[$i];
	$y0 = $pdf->getY();
	
	$pdf->SetFont('Helvetica','',20);
	$pdf->setX($x);
	$pdf->MultiCell(80, 8, "Nutzerdaten");
	$pdf->SetFont('Helvetica', '', 12);
	$pdf->setX($x);
	$pdf->MultiCell(90, 8, "Nutzer: " . $user[3] . " " . $user[4], 0, "L");
	$pdf->SetFont('Helvetica', '', 10);
	$pdf->setX($x);
	$pdf->MultiCell(90, 8, "http://mebis.bayern.de", 0, "L");
	$pdf->setFillColor(220, 220, 220);
	$y = $pdf->getY();
	$pdf->setX($x);
	$pdf->Cell(90, 20, "", 1, 0, 0, 1);
	$pdf->setXY($x, $y);
	$pdf->setX($x);
	$pdf->MultiCell(30, 8, "Anmeldename: ", 1, "L", false, 0);
	$pdf->MultiCell(60, 8, $user[1], 1, "L");
	$pdf->setX($x);
	$pdf->MultiCell(30, 8, "Kennwort: ", 1, "L", false, 0);
	$pdf->MultiCell(60, 8, $user[2], 1, "L");
	
	if ($col2) {
		$pdf->Ln(20);
		$x = $x0;
                if ($i % 8 == 0) {
                   $pdf->addPage();
                }
		$y = $pdf->getY();
	} else {
		$x = 110;
		$y = $y0;
	}
	
	$pdf->setXY($x, $y);
	$col2 = ($col2 + 1) % 2;
}

// Show pdf
$pdf->Output($filename,"I");
	
?>
