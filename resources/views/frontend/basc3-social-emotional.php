<?php

/**
 * Template Name: BASC-3 Social Emotional Original
 * A full-width BASC-3 Social Emotional  template.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if (!defined('ABSPATH')) {
	exit('Direct script access denied.');
}
session_start();

$_SESSION['page_type'] = 'report';
?>

<?php get_header(); ?>
<section id='content' class='full-width'>
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<div id='post-<?php the_ID(); ?>' <?php post_class(); ?>>
			<?php echo fusion_render_rich_snippets_for_pages(); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
			<?php avada_singular_featured_image(); ?>
			<div class='post-content'>
				<?php the_content(); ?>
				<?php fusion_link_pages(); ?>
			</div>
			<?php if (!post_password_required($post->ID)) : ?>
				<?php if (Avada()->settings->get('comments_pages')) : ?>
					<?php comments_template(); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endwhile; ?>

	<!-- <script src='jquery-3.6.0.min.js'></script> -->
	<style type='text/css'>
		@font-face {
			font-family: 'mvboli';
			src: url(/css/fonts/mvboli.ttf) format('truetype');
		}

		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: .9em;
			color: #000000;
			background-color: #FFFFFF;
			margin: 0;
			padding: 10px 20px 20px 20px;
		}

		samp {
			font-size: 1.3em;
		}

		.black-mask {
			background-color: #000 !important;
		}

		a {
			color: #000000;
			/* background-color: #FFFFFF; */
		}

		sup a {
			text-decoration: none;
		}

		hr {
			margin-left: 90px;
			height: 1px;
			color: #000000;
			background-color: #000000;
			border: none;
		}

		#logo {
			margin-bottom: 10px;
			margin-left: 28px;
		}

		.text {
			width: 80%;
			margin-left: 90px;
			line-height: 140%;
		}

		h3 {
			font-weight: bold;
			font-style: underline;
			margin: 0;
		}

		h4 {
			font-weight: normal;
			margin: 0;
		}

		.left {
			width: 50%;
			display: inline-block;
		}

		.right {
			width: 49%;
			float: right;
			display: inline-block;
		}

		.mvboli {
			font-family: 'mvboli';
		}

		.text-white {
			color: #fff;
		}

		.text-center {
			text-align: center !important;
		}

		.text-left {
			text-align: left !important;
		}

		.text-right {
			text-align: right !important;
		}

		.bold {
			font-weight: bold;
		}

		.grey,
		.grey td {
			background: #cfcfcf;
		}

		.grey td:first-child {
			font-weight: bold;
		}

		table tr td:nth-child(2),
		table tr td:nth-child(3),
		table tr td:nth-child(4) {
			text-align: center;
			width: 200px;
		}

		td.indent {
			padding-left: 10px;
		}

		table {
			border-collapse: collapse;
			width: 100%;
		}

		thead {
			font-weight: bold;
			text-align: center;
		}

		thead td {
			padding: 0 5px;
		}

		td {
			padding: 0;
			margin: 0;
			background: transparent;
		}

		td:first-child {
			width: 155px;
		}

		td:last-child {
			width: 195px;
		}


		.table-container td,
		.table-header {
			border: 1px solid #686868;
		}

		.table-header {
			border-top: 3px solid #000000;
		}

		td input {
			border: none;
			text-align: center;
			background-color: #ffffdb;
			line-height: 100%;
			margin: 0;
			width: 100%;
			box-sizing: border-box;
		}

		td input[type='checkbox'] {
			width: 40px
		}

		td input {
			height: auto !important;
		}

		td input.full {
			min-height: 25px;
			width: 100%;
			background-color: transparent;
			float: left;
			text-align: center;
		}

		.cu_lable {
			text-align: right;
			/* width: 40%; */
			float: right;
			margin-top: 5px !important;
			display: none;
			margin-right: 11px;
		}

		td select {
			background-color: #ffffdb;
		}

		table tr td span {
			font-family: Arial, Helvetica, sans-serif;
			/* background: transparent !important; */
		}

		table p {
			margin: 0;
			font-family: Arial, Helvetica, sans-serif;
			/* background: transparent !important; */
		}

		table tr td {
			font-family: Arial, Helvetica, sans-serif;
			/* background: transparent !important; */
		}

		.blank {
			background-color: #000 !important;
		}

		.generate-narrative {
			font-size: 24px;
			padding: 10px;
			background: #d9ead5;
			display: block;
			margin: auto;
			width: 75%;
			max-width: 320px;
		}

		.narrative {
			border: 6px dashed #cfcfcf;
			padding: 0 10px;
		}

		.narrative-title {
			background: #a5a5a5;
		}

		.narrative-title h1 {
			padding: 10px;
			margin: 2px;
			font-weight: 300;
		}

		.summary-title {
			margin-top: 40px;
			background: #a5a5a5;
			position: relative;
		}

		.summary-title h1 {
			padding: 10px;
			margin: 2px;
			font-weight: 300;
		}

		.copy-btn img {
			max-height: 200px;
			height: 200px;
		}

		.action-buttons {
			margin: 40px 0;
		}

		.examinee-table {
			margin: 40px 0;
		}

		.copy-buttons {
			text-align: center;
			margin: 20px 0;
			position: relative;
		}

		.copied-table {
			display: none;
			position: absolute;
			right: 0;
			background: #ceface;
			color: green;
			padding: 20px;
			top: 35%;
			font-weight: bold;
			border: 2px solid green;
		}

		.p_30 {
			padding: 2px;
			background: #d1eff9;
		}

		#show-rows {
			margin-bottom: 20px;
		}

		#show-rows td {
			text-align: left;
			border: none;
		}

		#show-rows thead td {
			text-align: center;
		}

		#narrative-container {
			display: none;
		}


		.ext-buttons {
			text-align: center;
			margin: 10px 0;
		}

		.faq-btn {
			background: #ffff00;
			font-size: 24px;
			padding: 10px;
			display: block;
			margin: auto;
			width: 75%;
			max-width: 158px;
			padding-right: 4px;
			display: inline;
		}

		.instructions-btn {
			background: #2e95fc;
			font-size: 24px;
			padding: 10px;
			display: block;
			margin: auto;
			width: 75%;
			max-width: 158px;
			display: inline;
		}

		a,
		a button {
			text-decoration: none;
		}

		a:hover,
		a button:hover,
		button:hover {
			cursor: pointer;
		}

		/* .table-container {
			border: 3px solid #000000;
		} */

		.table-header {
			padding: 10px;
			text-align: center;
		}

		.first_name,
		.capitalize {
			display: inline-block;
		}

		.capitalize::first-letter {
			text-transform: capitalize;
		}

		.confidence-interval {
			display: none;
		}

		.show_hide_fu3,
		.show_hide_fu2 {
			text-align: center;
		}

		.confidence-interval.show {
			display: table-cell;
		}

		.w-100 {
			width: 100%;
		}

		.text-values {
			display: none;

		}

		.orange-color {
			color: #c08703 !important;
			font-weight: bold !important;
		}

		.hide-bell-curve {
			display: none;
		}

		.informant_one {
			line-height: 50px;
		}

		.wit {
			width: 15% !important;
		}

		.m_b {
			margin-bottom: 10px;
			height: 10px;
		}

		.m_0 {
			margin-top: 0px;
		}

		.text-center {
			text-align: center !important;
		}

		.drop_style {
			border: 1px solid #dddbdb;
			text-align: center;
			line-height: 100%;
			margin-bottom: 5px;
			background-color: #fff;
			width: 100%;
			box-sizing: border-box;
		}

		.m_bt {
			margin-bottom: 5px;
		}

		/* .p_0 {
			margin: 0px;
			margin-bottom: 5px;
			padding-left: 20px;
		} */

		.text_color_t {
			color: #e7a716;
		}

		.hide {
			display: none !important;
		}

		.p_r {
			padding-right: 30px;
		}

		.bg_color {
			background-color: #000 !important;
			min-height: 25px;
			height: 40px;
		}

		.cap {
			text-transform: capitalize;
		}

		/*---------------custom table----------------------*/
		.table-container td.td_border_left,
		.table-header {
			border-left: 3px solid #000000;
		}

		.table-container tbody td:first-child {
			border-left: 3px solid #000000;
		}

		.table-container td.td_border_right,
		.table-header,
		.table-container-2 td.td_border_right {
			border-right: 3px solid #000000;
		}

		.light_grey td.re_left_border {
			border-left: none;
		}

		.light_grey td.re_right_border {
			border-right: none;
		}

		.light_grey td.re_border {
			border: none;
		}

		td.confidence-interval {
			width: 90px !important;
		}

		tr td.w-50 {
			width: 50px !important;
		}

		tr td.w-170 {
			width: 170px !important;
		}

		/* secound table style */

		.table-header-2 {
			display: none;
		}

		.table-container-2 td {
			border: none;
			line-height: 25px;
		}

		.table-container-2 td:first-child {
			border-left: 3px solid #000000 !important;
			border-right: 1px solid #000;
		}

		.table-container-2 td.top {
			border-top: 3px solid #000;
		}

		.table-container-2 td.com-bottom {
			border-right: 1px solid;
		}

		.table-container-2 td.bottom {
			border-bottom: 3px solid #000;
		}

		.table-container-2 .last_row {
			border-top: 1px solid;
		}

		.table-container-2 {
			width: 70%;
			margin-left: 15%;
		}

		/* .table-container-2 .table-cells-2{
			border: 3px solid #000000;
		} */

		.w-40 {
			width: 40% !important;
		}

		#table-container .last-row.score_td td {
			font-size: 10px;
		}

		#table-container .score_td td:nth-child(3),
		#table-container .score_td td:nth-child(4),
		#table-container .score_td td:nth-child(5),
		#table-container .score_td td:nth-child(6),
		#table-container .score_td td:nth-child(7) {
			width: 7% !important;
		}

		#table-container .score_td td:first-child {
			width: 20%;
		}

		.score_td span,
		.score_td label,
		.score_td p,
		.score_td td {
			font-size: 11px !important;
		}

		.copied-table-picture {
			display: none;
			position: absolute;
			right: 0;
			background: #ceface;
			color: green;
			padding: 20px;
			top: 35%;
			font-weight: bold;
			border: 2px solid green;
		}

		.ur {
			height: 10px;
			/* white-space: nowrap; */
		}

		.td-gap {
			margin-top: 75px;
		}

		.cp_size {
			/* width: 150%; */
			/* white-space: nowrap; */
			min-height: 30px;
		}

		.cp_size-a {
			width: 150%;
			/* white-space: nowrap; */
			/* min-height: 30px; */
		}

		.gry_cl {
			background-color: #cfcfcf !important;
			;
		}

		/* .ur-a{
			min-height: 2px !important;
		} */
	</style>
	<div class='left'>
		<div class='p_30 m_bt'>
			<table id='show-rows-1'>
				<thead>
					<tr>
						<td colspan='4'>Select the number of raters if more than one:<br><em style="font-size: 12px;">(please note if 4 or 5 is selected this will show up in the chart but the narrative will need to be manually updated)</em></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class='text-center' style='width:25%;'><input type='checkbox' attr="show_hide_fu0" class='show_hide_fu' id="0" value='Two-row'>
							<label for='show_Two'>Two</label>
						</td>
						<td class='text-center' style='width:25%;'><input type='checkbox' attr="show_hide_fu1" class='show_hide_fu' id="1" value='Three-row'>
							<label for='show_Three'>Three</label>
						</td>
						<td class='text-center' style='width:25%;'><input type='checkbox' attr="show_hide_fu2" class='show_hide_fu' id="2" value='four-row'>
							<label for='show_Two'>Four</label>
						</td>
						<td class='text-center' style='width:25%;'><input type='checkbox' attr="show_hide_fu3" class='show_hide_fu' id="3" value='five-row'>
							<label for='show_Three'>Five</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class='p_30 ' style="width: 40%; float: left;">
			<table id='show-rows-2'>
				<thead>
					<tr>
						<td colspan='3'>Check box for Preschool Battery:</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class='text-center indent' style="width: 33%;"><input type='checkbox' id="pre_school_h"><label for='Preschool'>Preschool</label></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class='p_30' style="width: 59%; float: left; margin-left: 1%;">
			<table id='show-rows-3'>
				<thead>
					<tr>
						<td colspan='3'>Check box if area NOT assessed:</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class='text-center' style='width:50%;'><input type='checkbox' attr="Social" class='Social' id="Social" value='Social'>
							<label for='show_Two'>Social Emotional</label>
						</td>
						<td class='text-center' style='width:50%;'><input type='checkbox' attr="adaptive" class='adaptive' id="adaptive" value='adaptive'>
							<label for='show_Three'>Adaptive Skills</label>
					</tr>
				</tbody>
			</table>
		</div>
		<div class='table-container' id='table-container' style="margin-top: 75px;">
			<div class='table-header'>
				<h3>Behavior Assessment for Children- Third Edition (BASC-3)</h3>
				<h4>(All scores are reported as T-scores Mean= 50, Standard Deviation= 10, Average T-score range= 41-59)</h4>
			</div>
			<table id='table-cells'>
				<thead>
					<tr class='grey'>
						<td class="td_border_left">
							<p>Social/Emotional Functioning BASC-3</p>
						</td>
						<td>
							<p>Scale Definitions</p>
						</td>
						<td class="wit cap show_hide_fu" id="informer_title_t0">
							<p>0</p>
						</td>
						<td class="hide cap show_hide_fu0 wit" id="informer_title_t1">
							<p>0</p>
						</td>
						<!--td><p>Description of Scale</p></td-->
						<td class="hide cap show_hide_fu1 wit" id="informer_title_t2">
							<p>0</p>
							<!-- <td class="confidence-interval">
							<p>Confidence Interval</p>
						</td> -->
						</td>
						<td class="hide cap show_hide_fu2 wit" id="informer_title_t3">
							<p>0</p>
						</td>
						<td class="hide cap show_hide_fu3 wit" id="informer_title_t4">
							<p>0</p>
						</td>
						<!--td><p>Description of Scale</p></td-->
					</tr>
				</thead>
				<tbody class="Social-body" id="tcell_Social">

					<tr class="1 grey ">
						<td>
							<p>Externalizing Problems</p>
						</td>
						<td>
							<p></p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="externalizing_problems_1" value="50"><span class="total_memoryindex text-values" style="float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="externalizing_problems_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="externalizing_problems_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="externalizing_problems_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="externalizing_problems_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="externalizing_problems_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="externalizing_problems_4" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="externalizing_problems_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="externalizing_problems_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="externalizing_problems_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="externalizing_problems_ci" id="externalizing_problems_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>


					<tr class="2  ">
						<td>
							<p>Hyperactivity</p>
						</td>
						<td>
							<p>The tendency to be overly active, rush through work or activities, and act without thinking.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="hyperactivity_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="hyperactivity_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="hyperactivity_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="hyperactivity_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="hyperactivity_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="hyperactivity_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="hyperactivity_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="hyperactivity_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="hyperactivity_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="hyperactivity_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="hyperactivity_ci" id="hyperactivity_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="3  ">
						<td>
							<p>Aggression</p>
						</td>
						<td>
							<p>The tendency to act in a hostile manner (either verbally or physically) that is threatening to others.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="aggression_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="aggression_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="aggression_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="aggression_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="aggression_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="aggression_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="aggression_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="aggression_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="aggression_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="aggression_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>

						<td class="confidence-interval"><input type="text" name="aggression_ci" id="aggression_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="4 pre">
						<td>
							<p>Conduct Problems</p>
						</td>
						<td>
							<p>Characterized by anti-social behavior and is frequently associated with low self-esteem, poor frustration tolerance, lack of empathy, risk taking behaviors, and impairments in relationships.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="conduct_problems_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="conduct_problems_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="conduct_problems_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="conduct_problems_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="conduct_problems_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="conduct_problems_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="conduct_problems_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="conduct_problems_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="conduct_problems_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="conduct_problems_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="conduct_problems_ci" id="conduct_problems_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="5 grey ">
						<td>
							<p>Internalizing Problems</p>
						</td>
						<td>
							<p></p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="internalizing_problems_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="internalizing_problems_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="internalizing_problems_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="internalizing_problems_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="internalizing_problems_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="internalizing_problems_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="internalizing_problems_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="internalizing_problems_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="internalizing_problems_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="internalizing_problems_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="internalizing_problems_ci" id="internalizing_problems_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="6  ">
						<td>
							<p>Anxiety</p>
						</td>
						<td>
							<p>The tendency to be nervous, fearful, or worried about real or imagined problems.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="anxiety_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="anxiety_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="anxiety_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="anxiety_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="anxiety_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="anxiety_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="anxiety_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="anxiety_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="anxiety_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="anxiety_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="anxiety_ci" id="anxiety_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="7  ">
						<td>
							<p>Depression</p>
						</td>
						<td>
							<p>Feelings of unhappiness, sadness, and stress that may result in an inability to carry out everyday activities or may bring thoughts of suicide.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="depression_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="depression_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="depression_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="depression_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="depression_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="depression_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="depression_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="depression_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="depression_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="depression_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="depression_ci" id="depression_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="8  ">
						<td>
							<p>Somatization</p>
						</td>
						<td>
							<p>The tendency to be overly sensitive to and complain about relatively minor physical problems and discomforts.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="somatization_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="somatization_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="somatization_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="somatization_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="somatization_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="somatization_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="somatization_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="somatization_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="somatization_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="somatization_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="somatization_ci" id="somatization_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="9 grey pre">
						<td>
							<p>School Problems</p>
						</td>
						<td>
							<p></p>
						</td>
						<td class="show_hide_fu black_out0">
							<input type="text" class="percentile full" id="school_problems_1" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="school_problems_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 black_out1">
							<input type="text" class="percentile full" id="school_problems_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="school_problems_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="school_problems_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="school_problems_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="school_problems_4" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="school_problems_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="school_problems_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="school_problems_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>

						<td class="confidence-interval"><input type="text" name="school_problems_ci" id="school_problems_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="10 pre">
						<td>
							<p>Attention Problems</p>
						</td>
						<td>
							<p>The tendency to be easily distracted and unable to concentrate more than momentarily.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="attention_problems_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="attention_problems_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="attention_problems_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="attention_problems_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="attention_problems_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="attention_problems_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="attention_problems_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="attention_problems_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="attention_problems_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="attention_problems_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="attention_problems_ci" id="attention_problems_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="11 pre">
						<td>
							<p>Learning Problems</p>
						</td>
						<td>
							<p>Characterized by reading problems or having trouble keeping up in class.</p>
						</td>
						<td class="show_hide_fu black_out0">
							<input type="text" class="percentile full" id="learning_problems_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="learning_problems_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 black_out1">
							<input type="text" class="percentile full" id="learning_problems_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="learning_problems_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="learning_problems_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="learning_problems_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="learning_problems_4" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="learning_problems_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="learning_problems_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="learning_problems_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="learning_problems_ci" id="learning_problems_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="12 grey ">
						<td>
							<p>Behavioral Symptoms</p>
						</td>
						<td>
							<p></p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="behavioral_symptoms_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="behavioral_symptoms_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="behavioral_symptoms_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="behavioral_symptoms_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="behavioral_symptoms_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="behavioral_symptoms_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="behavioral_symptoms_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="behavioral_symptoms_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="behavioral_symptoms_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="behavioral_symptoms_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="behavioral_symptoms_ci" id="behavioral_symptoms_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="13  ">
						<td>
							<p>Atypicality</p>
						</td>
						<td>
							<p>The tendency to behave in ways that are immature, considered “odd”, or commonly associated with psychosis (such as experiencing visual or auditory hallucinations).</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="atypicality_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="atypicality_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="atypicality_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="atypicality_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="atypicality_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="atypicality_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="atypicality_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="atypicality_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="atypicality_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="atypicality_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="atypicality_ci" id="atypicality_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="14  ">
						<td>
							<p>Withdrawal</p>
						</td>
						<td>
							<p>The tendency to evade others to avoid social contact.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="withdrawal_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="withdrawal_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="withdrawal_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="withdrawal_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="withdrawal_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="withdrawal_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="withdrawal_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="withdrawal_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="withdrawal_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="withdrawal_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="withdrawal_ci" id="withdrawal_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="15 hide">
						<td>
							<p>Attention Problems</p>
						</td>
						<td>
							<p>The tendency to be easily distracted and unable to concentrate more than momentarily.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="Attention_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="Attention_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="Attention_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="Attention_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="Attention_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="Attention_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="Attention_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="Attention_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="Attention_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="Attention_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="Attention_ci" id="Attention_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>

				</tbody>
				<!-- ///////////////////////////////////////////////////////////////////////////////// -->
				<tbody class="adaptive-body">
					<tr class="16 grey">
						<td>
							<p>ADAPTIVE SKILLS(T-scores Reversed)</p>
						</td>
						<td>
							<p id="adaptive_pr"></p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="adaptive_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="adaptive_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="adaptive_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptive_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="adaptive_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptive_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="adaptive_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptive_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="adaptive_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptive_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="adaptive_ci" id="adaptive_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="17  ">
						<td>
							<p>Adaptability</p>
						</td>
						<td>
							<p id="adaptability_pr">This scale assesses the ability to adjust to changes in routine and new teachers, to shift from one task to another, and to share possessions with other children.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="adaptability_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="adaptability_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="adaptability_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptability_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="adaptability_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptability_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="adaptability_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptability_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="adaptability_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="adaptability_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="adaptability_ci" id="adaptability_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="18  ">
						<td>
							<p>Social Skills</p>
						</td>
						<td>
							<p id="social_skills_pr">The skills necessary for interacting successfully with peers and adults in home, school, and community settings.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="social_skills_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="social_skills_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="social_skills_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="social_skills_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="social_skills_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="social_skills_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="social_skills_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="social_skills_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="social_skills_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="social_skills_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="social_skills_ci" id="social_skills_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="19  pre">
						<td>
							<p>Study Skills</p>
						</td>
						<td>
							<p id="study_skills_pr">Characterized by reading assigned chapters or trying to do well in school.</p>
						</td>
						<td class="show_hide_fu black_out0">
							<input type="text" class="percentile full" id="study_skills_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="study_skills_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 black_out1"><input type="text" class="percentile full" id="study_skills_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="study_skills_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 black_out2"><input type="text" class="percentile full" id="study_skills_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="study_skills_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 black_out3"><input type="text" class="percentile full" id="study_skills_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="study_skills_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 black_out4"><input type="text" class="percentile full" id="study_skills_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="study_skills_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="study_skills_ci" id="study_skills_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="20  pre">
						<td>
							<p>Leadership</p>
						</td>
						<td>
							<p id="leadership_pr">Gives good suggestions for solving problems or is good at getting people to work together</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="leadership_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="leadership_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="leadership_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="leadership_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="leadership_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="leadership_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="leadership_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="leadership_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="leadership_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="leadership_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="leadership_ci" id="leadership_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="21  ">
						<td>
							<p>Activities of Daily Living</p>
						</td>
						<td>
							<p id="daily_living_pr">Skills associated with performing basic, everyday tasks in an acceptable and safe manner.</p>
						</td>
						<td class="show_hide_fu black_out0-2"><input type="text" class="percentile full" id="daily_living_1" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="daily_living_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 black_out1-2">
							<input type="text" class="percentile full" id="daily_living_2" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="daily_living_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 black_out2-2">
							<input type="text" class="percentile full" id="daily_living_3" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="daily_living_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 black_out3-2">
							<input type="text" class="percentile full" id="daily_living_4" value="50"><span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="daily_living_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 black_out4-2">
							<input type="text" class="percentile full" id="daily_living_5" value="50">
							<span class="total_memoryindex text-values" style="display: none;float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="daily_living_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="confidence-interval"><input type="text" name="daily_living_ci" id="daily_living_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>


					<tr class="22  ">
						<td>
							<p>Functional Communication</p>
						</td>
						<td>
							<p id="functional_communication_pr">The skills needed to communicate feelings, present ideas, obtain needed information, and in general to communicate clearly.</p>
						</td>
						<td class="show_hide_fu"><input type="text" class="percentile full" id="functional_communication_1" value="50"><span class="total_memoryindex text-values" style="display: none; float: left !important;margin-left: 15px!important;margin-top: 7px !important;">0</span>
							<lable la-attr="functional_communication_1" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu0 "><input type="text" class="percentile full" id="functional_communication_2" value="50"><span class="total_memoryindex text-values" style="display: none; float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="functional_communication_2" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu1 "><input type="text" class="percentile full" id="functional_communication_3" value="50">
							<span class="total_memoryindex text-values" style="display: none; float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="functional_communication_3" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<td class="hide show_hide_fu2 "><input type="text" class="percentile full" id="functional_communication_4" value="50"><span class="total_memoryindex text-values" style="display: none; float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="functional_communication_4" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
						<td class="hide show_hide_fu3 "><input type="text" class="percentile full" id="functional_communication_5" value="50">
							<span class="total_memoryindex text-values" style="display: none; float: left !important;margin-left: 15px!important;margin-top: 7px !important;">96</span>
							<lable la-attr="functional_communication_5" class="cu_lable" style="display: block;margin-top: 5px !important ;font-size: 22px !important;"></lable>
						</td>

						<td class="confidence-interval"><input type="text" name="functional_communication_ci" id="functional_communication_ci" value=""><span class="total_memoryindex_ci text-values"></span></td>

						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>


					<tr class="23 last-row">
						<td class="text-right" style="border: none; padding-right: 10px;">*At Risk</td>
						<td class="text-center" style="border: none;">**Clinically Significant</td>
						<td style="border: none;width:50px;">(Blacked Out=N/A)</td>
					</tr>
				</tbody>
			</table>

		</div>

		<table class='examinee-table' id='fir_name'>
			<tr class=''>
				<td><label for='first_name' class='text_color_t orange-color'>Examinee First Name:</label></td>
				<td><input type='text' name='first_name' id='first_name' autocomplete="off"></td>
				<!-- <td class='text-right indent w-50'><input type="checkbox" attr="confidence-interval" name='include_confidence_intervals' id='include_confidence_intervals'></td> -->
				<!-- <td class='text-left w-170'><label for='include_confidence_intervals'>Check this box to include a column for confidence intervals</label></td> -->
				<!-- <td class='text-right indent w-50'></td> -->
				<td class='text-left w-170'></td>
			</tr>
			<tr class='m_b'></tr>
			<tr class=''>
				<td><label for='gender' class=' orange-color'>Examinee Gender:</label></td>
				<td>
					<select name='gender' id='gender' class='w-100'>
						<option value='male'>Male</option>
						<option value='female'>Female</option>
						<option value='neutral'>Neutral</option>
					</select>
				</td>
				<!-- <td class='text-right indent w-50'></td> -->
				<td class='text-left w-170'></td>
			</tr>
			<tr class='m_b'></tr>

		</table>
		<!-- <table class='examinee-table m_0'>
			<tr>
				<td class='orange-color'>Check If Preschool From Was Used:</td>
				<td class='text-left indent'><input type='checkbox' name='include_confidence_intervals' id='include_confidence_intervals'><label for='Preschool'>Preschool</label></td>
				<td class='text-left w-170'></td>
			</tr>
		</table> -->
		<table class='examinee-table m_0'>
			<tr class='0'>
				<td class='orange-color'>Rater One:</td>
				<td class='text-center '><input type='text' id="informant_one" class='informer_name' attr_title='informer_title0' name='include_confidence_intervals' style='float:left;height: 50px !important;' id='informant_one' autocomplete="off">
				<td class='text-left w-170'><select class='drop_style' id="informant_one_relationship" style='float:left;'>
						<option ch_attr="Parent" value="mother">Mother</option>
						<option ch_attr="Parent" value="father">Father</option>
						<option ch_attr="Parent" value="grandmother">Grandmother</option>
						<option ch_attr="Parent" value="grandfather">Grandfather</option>
						<option ch_attr="Parent" value="aunt">Aunt</option>
						<option ch_attr="Parent" value="uncle">Uncle</option>
						<option ch_attr="Parent" value="legal guardian">Legal Guardian</option>
						<option ch_attr="teacher" value="math teacher">Math Teacher</option>
						<option ch_attr="teacher" value="english teacher">English Teacher</option>
						<option ch_attr="teacher" value="social studies teacher">Social Studies Teacher</option>
						<option ch_attr="teacher" value="science teacher">Science Teacher</option>
						<option ch_attr="teacher" value="elective teacher">Elective Teacher</option>
						<option ch_attr="teacher" value="history teacher">History Teacher</option>
						<option ch_attr="teacher" value="special education teacher">Special Education Teacher</option>
						<option ch_attr="teacher" value="general education teacher">General Education Teacher</option>
						<option ch_attr="teacher" value="curriculumcoach">curriculum coach</option>
						<option ch_attr="teacher" value="interventionist">Interventionist</option>
					</select>
				</td>
				</td>
			</tr>
			<tr class='hide show_hide_fu0'>
				<td class='orange-color'>Rater Two:</td>
				<td class='text-center '><input type='text' id="informant_two" class='informer_name' attr_title='informer_title1' name='include_confidence_intervals' style='float:left;height: 50px !important;' id='informant_two' autocomplete="off">

				</td>
				<!-- <td class='text-right  w-50'></td> -->
				<td class='text-left w-170'>
					<select class='drop_style' id="informant_two_relationship" style='float:left;'>
						<option ch_attr="Parent" value="mother">Mother</option>
						<option ch_attr="Parent" value="father">Father</option>
						<option ch_attr="Parent" value="grandmother">Grandmother</option>
						<option ch_attr="Parent" value="grandfather">Grandfather</option>
						<option ch_attr="Parent" value="aunt">Aunt</option>
						<option ch_attr="Parent" value="uncle">Uncle</option>
						<option ch_attr="Parent" value="legal guardian">Legal Guardian</option>
						<option ch_attr="teacher" value="math teacher">Math Teacher</option>
						<option ch_attr="teacher" value="english teacher">English Teacher</option>
						<option ch_attr="teacher" value="social studies teacher">Social Studies Teacher</option>
						<option ch_attr="teacher" value="science teacher">Science Teacher</option>
						<option ch_attr="teacher" value="elective teacher">Elective Teacher</option>
						<option ch_attr="teacher" value="history teacher">History Teacher</option>
						<option ch_attr="teacher" value="special education teacher">Special Education Teacher</option>
						<option ch_attr="teacher" value="general education teacher">General Education Teacher</option>
						<option ch_attr="teacher" value="curriculum coach">curriculum Coach</option>
						<option ch_attr="teacher" value="interventionist">Interventionist</option>
					</select>
				</td>
			</tr>
			<tr class='hide show_hide_fu1'>
				<td class='orange-color'>Rater Three:</td>
				<td class='text-center '><input type='text' id="informant_three" class='informer_name' attr_title='informer_title2' name='include_confidence_intervals' style='float:left;height: 50px !important;' id='informant_three' autocomplete="off">

				</td>
				<!-- <td class='text-right  w-50'></td> -->
				<td class='text-left w-170'>
					<select class='drop_style' id="informant_three_relationship" style='float:left;'>
						<option ch_attr="teacher" value="math teacher">Math Teacher</option>
						<option ch_attr="teacher" value="english teacher">English Teacher</option>
						<option ch_attr="teacher" value="social studies teacher">Social Studies Teacher</option>
						<option ch_attr="teacher" value="science teacher">Science Teacher</option>
						<option ch_attr="teacher" value="elective teacher">Elective Teacher</option>
						<option ch_attr="teacher" value="history teacher">History Teacher</option>
						<option ch_attr="teacher" value="special education teacher">Special Education Teacher</option>
						<option ch_attr="teacher" value="general education teacher">General Education Teacher</option>
						<option ch_attr="teacher" value="curriculum coach">Curriculum Coach</option>
						<option ch_attr="teacher" value="interventionist">Interventionist</option>
					</select>
				</td>
			</tr>
			<tr class='hide show_hide_fu2'>
				<td class='orange-color text-left'>Rater Four:</td>
				<td class='text-center '><input type='text' id="informant_four" class='informer_name' attr_title='informer_title3' name='include_confidence_intervals' style='float:left;height: 50px !important;' id='informant_three' autocomplete="off">

				</td>
				<!-- <td class='text-right  w-50'></td> -->
				<td class='text-left w-170'>
					<select class='drop_style' id="informant_three_relationship" style='float:left;'>
						<option ch_attr="teacher" value="math teacher">Math Teacher</option>
						<option ch_attr="teacher" value="english teacher">English Teacher</option>
						<option ch_attr="teacher" value="social studies teacher" selected>Social Studies Teacher</option>
						<option ch_attr="teacher" value="science teacher">Science Teacher</option>
						<option ch_attr="teacher" value="elective teacher">Elective Teacher</option>
						<option ch_attr="teacher" value="history teacher">History Teacher</option>
						<option ch_attr="teacher" value="special education teacher">Special Education Teacher</option>
						<option ch_attr="teacher" value="general education teacher">General Education Teacher</option>
						<option ch_attr="teacher" value="curriculum coach">Curriculum Coach</option>
						<option ch_attr="teacher" value="interventionist">Interventionist</option>
					</select>
				</td>
			</tr>
			<tr class='hide show_hide_fu3'>
				<td class='orange-color text-left'>Rater Five:</td>
				<td class='text-center '><input type='text' id="informant_five" class='informer_name' attr_title='informer_title4' name='include_confidence_intervals' style='float:left;height: 50px !important;' id='informant_three' autocomplete="off">

				</td>
				<!-- <td class='text-right  w-50'></td> -->
				<td class='text-left w-170'>
					<select class='drop_style' id="informant_three_relationship" style='float:left;'>
						<option ch_attr="teacher" value="math teacher">Math Teacher</option>
						<option ch_attr="teacher" value="english teacher">English Teacher</option>
						<option ch_attr="teacher" value="social studies teacher">Social Studies Teacher</option>
						<option ch_attr="teacher" value="science teacher">Science Teacher</option>
						<option ch_attr="teacher" value="elective teacher">Elective Teacher</option>
						<option ch_attr="teacher" value="history teacher">History Teacher</option>
						<option ch_attr="teacher" value="special education teacher">Special Education Teacher</option>
						<option ch_attr="teacher" value="general education teacher" selected>General Education Teacher</option>
						<option ch_attr="teacher" value="curriculum coach">Curriculum Coach</option>
						<option ch_attr="teacher" value="interventionist">Interventionist</option>
					</select>
				</td>
			</tr>
		</table>
		<table class='examinee-table m_0 Social-body'>
			<thead class='informant_one 0'>
				<tr>
					<td class='text-left '></td>
					<td class='text_color_t cap' id='informer_title0'>0</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
			</thead>
			<tbody>
				<tr class='0'>
					<td>F-Index:</td>
					<td>
						<select name='additional_title' id='f_index' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
				<tr class='0'>
					<td>Response Pattern:</td>
					<td>
						<select name='additional_title' id='Response_Pattern' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
				<tr class='0'>
					<td>Consistency:</td>
					<td>
						<select name='additional_title' id='Consistency' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
			</tbody>
			<thead class='informant_one hide show_hide_fu0'>
				<tr>
					<td class='text-left '></td>
					<td class='text_color_t cap' id='informer_title1'>0</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
			</thead>
			<tbody>
				<tr class='hide show_hide_fu0'>
					<td>F-Index:</td>
					<td>
						<select name='additional_title' id='f_index1' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
				<tr class='hide show_hide_fu0'>
					<td>Response Pattern:</td>
					<td>
						<select name='additional_title' id='Response_Pattern1' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
				<tr class='hide show_hide_fu0'>
					<td>Consistency:</td>
					<td>
						<select name='additional_title' id='Consistency1' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
			</tbody>
			<thead class='informant_one hide show_hide_fu1'>
				<tr>
					<td class='text-left '></td>
					<td class='text_color_t cap' id='informer_title2'>0</td>
				</tr>
				<!-- <td class='text-right  w-50'></td> -->
				<td class='text-left w-170'></td>
			</thead>
			<tbody>
				<tr class='hide show_hide_fu1'>
					<td>F-Index:</td>
					<td>
						<select name='additional_title' id='f_index2' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
				<tr class='hide show_hide_fu1'>
					<td>Response Pattern:</td>
					<td>
						<select name='additional_title' id='Response_Pattern2' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
				<tr class='hide show_hide_fu1'>
					<td>Consistency:</td>
					<td>
						<select name='additional_title' id='Consistency2' class='w-100 drop_style'>
							<option value='acceptable'>acceptable</option>
							<option value='caution'>caution</option>
							<option value='extreme caution'>extreme caution</option>
						</select>
					</td>
					<!-- <td class='text-right  w-50'></td> -->
					<td class='text-left w-170'></td>
				</tr>
			</tbody>
			<!-- <thead class='informant_one hide show_hide_fu2'>	<tr>		<td class='text-left '></td>		<td class='text_color_t cap' id='informer_title3'>0</td>	</tr>	<td class='text-left w-170'></td></thead> -->
			<!-- <tbody>	<tr class='hide show_hide_fu2'>		<td>F-Index:</td>		<td><select name='additional_title' id='f_index2' class='w-100 drop_style'>	<option value='acceptable'>acceptable</option>	<option value='caution'>caution</option>	<option value='extreme caution'>extreme caution</option></select>		</td>		<td class='text-left w-170'></td>	</tr>	<tr class='hide show_hide_fu2'>		<td>Response Pattern:</td>		<td><select name='additional_title' id='Response_Pattern2' class='w-100 drop_style'>	<option value='acceptable'>acceptable</option>	<option value='caution'>caution</option>	<option value='extreme caution'>extreme caution</option></select>		</td>		<td class='text-left w-170'></td>	</tr>	<tr class='hide show_hide_fu2'>		<td>Consistency:</td>		<td><select name='additional_title' id='Consistency2' class='w-100 drop_style'>	<option value='acceptable'>acceptable</option>	<option value='caution'>caution</option>	<option value='extreme caution'>extreme caution</option></select>		</td>		<td class='text-left w-170'></td>	</tr></tbody> -->
			<!-- <thead class='informant_one hide show_hide_fu3'>	<tr>		<td class='text-left '></td>		<td class='text_color_t cap' id='informer_title4'>0</td>	</tr>	<td class='text-left w-170'></td></thead><tbody>	<tr class='hide show_hide_fu3'>		<td>F-Index:</td>		<td><select name='additional_title' id='f_index2' class='w-100 drop_style'>	<option value='acceptable'>acceptable</option>	<option value='caution'>caution</option>	<option value='extreme caution'>extreme caution</option></select>		</td>
		<td class='text-left w-170'></td>	</tr>	<tr class='hide show_hide_fu3'>		<td>Response Pattern:</td>		<td><select name='additional_title' id='Response_Pattern2' class='w-100 drop_style'>	<option value='acceptable'>acceptable</option>	<option value='caution'>caution</option>	<option value='extreme caution'>extreme caution</option></select>		</td>
		<td class='text-left w-170'></td>	</tr>	<tr class='hide show_hide_fu3'>		<td>Consistency:</td>		<td><select name='additional_title' id='Consistency2' class='w-100 drop_style'>	<option value='acceptable'>acceptable</option>	<option value='caution'>caution</option>	<option value='extreme caution'>extreme caution</option></select>		</td>
		<td class='text-left w-170'></td>	</tr></tbody> -->
		</table>
		<div class='action-buttons'><button class='btn generate-narrative'>Generate Narrative</button>
			<?php include get_template_directory() . '/live files/psivey-container-test-extended.php'; ?>
			<div class='copy-buttons'>
				<a href='javascript:void(0);' class='copy-btn copy-table'><img src='/images/copy-table.png'></a>
				<a href='javascript:void(0);' class='copy-btn copy-graphs-table'><img src='/images/copy-chart.png'></a>
				<a href='javascript:void(0);' class='copy-btn copy-narrative'><img src='/images/copy-narrative.png'></a>
				<a href='javascript:void(0);' class='copy-btn copy-picture-of-table'><img src='/images/copy-picture-of-table.jpg'></a>
				<a href='javascript:void(0);' class='copy-btn copy-picture-of-graph'><img src='/images/copy-picture-of-chart.jpg'></a>
				<div class='copied-table'>Table Copied!</div>
				<div class='copied-table-picture'>Copied Picture!</div>
				<!-- <a href='javascript:;' class='copy-btn copy-tablecells'><img src='/images/copy-tablecells.png'></a> -->
			</div>
			<p class='text-center'><i></i></p>
		</div>
		<div class='canvas-container'>
			<style>
				canvas {
					display: none;
				}

				#img-out canvas,
				.canvas canvas {
					display: block !important;
					max-width: 100%;
					height: auto;
					width: 1000px;
				}
			</style>
			<a href='#' class='link-canvas' download>
				<div class='canvas'></div>
			</a>
		</div>
		<div id='explain-scr'></div>
	</div>
	<div class='right'>
		<div class='narrative-title'>
			<h1><span class='text-white bold mvboli'>ClickReport's</span> BASC-3 Social/Emotional Narrative</h1>
		</div>
		<div class='narrative'>
			<div class='narrative-instructions-container'>
				<p class='text-center'>Click 'Generate Narrative' to instantly generate the narrative here.</p>
			</div>
			<div class='narrative-container' id='narrative-container' style='display:none;'>

				<p class='Social-body_n'>Social/Emotional Functioning</p>

				<p class='Social-body_n'>Social-emotional development includes an individual's experience, expression, and management of emotions and behaviors. It includes the ability to establish positive and rewarding relationships with others and encompasses how well we manage ourselves as well as our interactions with others. In order to obtain information regarding <span class='first_name add_s'>the examinee</span> behavior at school and at home, the Behavior Assessment System for Children, Third Edition (BASC-3) was completed by <span class='his_her'>his</span> <span class="informant_one_relationship">mother</span><span class="additional_informers">,<span class="informer_one">the rater one</span>.</span> The BASC-3 is a multidimensional approach to evaluating the behavior of children and adolescents. It measures numerous aspects of behavior and personality. <span class='first_name capitalize cap add_s'>the examinee</span> functioning was assessed through three broad Composites: Externalizing Problems, Internalizing Problems, and School Problems.</p>

				<p class='externalizing_p m_bt Social-body_n'><b>Externalizing Problems:</b> This composite is characterized by under-control of emotions/behaviors that result in disruptive behaviors, such as aggression, hyperactivity, and conduct problems. <span class='first_name capitalize add_s'>the examinee</span> overall level of Externalizing Problems was rated to be in the <span class="externalizing_desc capitalize">average</span> range <span class="additional_del">on this measure.</span><span class="externalizing_te_trd hide"> suggesting that <span class='his_her'>his</span> externalizing behaviors are consistent across settings.</span> <span class="externalizing_te hide">The presence of an elevated score indicates that <span class='first_name'>the examinee</span> exhibits externalizing problems and may struggle to keep <span class='his_her'>his</span> impulses under control and has been observed to act out in various ways that are detrimental to <span class='his_her'>his</span> academic success.</span></p>
				<p class="hyperactivity_p p_0 Social-body_n"><em>Hyperactivity:</em> This scale assesses a student's activity levels. Typical hyperactive behaviors may include fidgeting, constant movement, and wandering. <span class='first_name capitalize'>the examinee</span> received ratings within the <span class="hyperactivity_desc capitalize">average</span> range <span class="additional_del">on this measure.</span><span class="hyperactivity_te_trd hide"> suggesting that <span class='his_her'>his</span> hyperactive behaviors are consistent across settings.</span> <span class="hyperactivity_te hide">The presence of an elevated score indicates that <span class='first_name'>the examinee</span> exhibits hyperactive behaviors such as rushing through work and acting without thinking.</span></p>
				<p class="aggression_p p_0 Social-body_n"><em>Aggression:</em> This scale measures a student's tendency to act in a hostile manner. This may include both verbal (threatening others) and physical (hitting/kicking) behaviors. <span class='first_name capitalize add_s'>the examinee</span> levels of aggression were rated in the <span class="aggression_desc capitalize">average</span> range <span class="additional_del">on this measure.</span><span class="aggression_te_trd hide"> suggesting that <span class='his_her'>his</span> aggression behaviors are consistent across settings.</span> <span class="aggression_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may exhibit aggressive behaviors such as using foul language or hitting/kicking others.</span></p>
				<p class="conduct_p p_0 Social-body_n pre"><em>Conduct Problems:</em> This scale provides a measure of how often <span class='first_name'>the examinee</span> is getting in trouble and is characterized by anti-social behavior and marked impairments in relationships. <span class='first_name capitalize'>the examinee</span> received scores within the <span class="conduct_desc capitalize">average</span> range <span class="additional_del">on this measure.</span> <span class="conduct_te hide">The presence of an elevated score indicates that <span class='first_name'>the examinee</span> may get in trouble a lot and has significant difficulties maintaining interpersonal relationships.</span></p>

				<p class='internalizing_p m_bt Social-body_n'><b>Internalizing Problems:</b> This composite is characterized by over-control of emotions/behaviors and not usually marked by acting out behavior. Instead, internalizing behaviors are often described as social withdrawal and/or feelings of nervousness and sadness. <span class='first_name capitalize add_s'>the examinee</span> overall level of Internalizing Problems was rated within the <span class="internalizing_desc capitalize">average</span> range <span class="additional_del">on this measure.</span><span class="internalizing_te_trd hide"> indicating that <span class='first_name add_s'>the examinee</span> internalizing behaviors are consistent across settings.</span> <span class="internalizing_te hide">The presence of an elevated score indicates that <span class='first_name'>the examinee</span> may be struggling with depression, anxiety, and/or low self-esteem.</span></p>
				<p class="anxiety_p p_0 Social-body_n"><em>Anxiety:</em> This scale measures worry, nervousness, and/or unease. <span class='first_name capitalize'>the examinee</span> received scores within the <span class="anxiety_desc capitalize">average</span> range <span class="additional_del">on this measure.</span> <span class="anxiety_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may be suffering with anxiety.</span></p>
				<p class="depression_p p_0 Social-body_n"><em>Depression:</em> Depression is a common and serious medical illness that negatively affects how one feels, thinks, and acts. This scale measures observable behaviors associated with depression such as loss of interest and energy, verbalizations of sadness, and pervasive sad moods. <span class='first_name capitalize'>the examinee</span> received ratings within the <span class="depression_desc capitalize">average</span> range <span class="additional_del">on this measure.</span> <span class="depression_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may be suffering with depression and thoughts of helplessness, hopelessness, and, in extreme cases, suicide.</span></p>
				<p class="somatization_p p_0 Social-body_n"><em>Somatization:</em> This scale assesses complaints of physical ailments or oversensitivity to seemingly normal physical problems. <span class='first_name capitalize'>the examinee</span> received scores within the <span class="somatization_desc capitalize">average</span> range <span class="additional_del">on this measure.</span> <span class="somatization_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may ask to go to the nurse's office a lot and may have an underlying problem associated with <span class="his_her">his</span> physical ailments that is not physical in nature.</span></p>

				<p class="school_problems_p p_0 Social-body_n pre"><b>School Problems:</b> This composite reflects academic difficulties including problems of motivation, attention, learning, and cognition. A high score on this composite is a sign that <span class="first_name">the examinee</span> displays behaviors that are very likely to interfere with achievement. <span class="school_pro_section"></span></p>
				<p class="attention_p p_0 Social-body_n pre"><em>Attention Problems:</em> This scale measures distractibility and focus. <span class='first_name capitalize add_s'>the examinee</span> attention was rated within the <span class="attention_desc cap">average</span> range <span class="additional_del1"><span class="informer_one">the rater one</span>.</span><span class="attention_te_trd hide"> indicating that these behaviors are seen across settings.</span> <span class="attention_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may have trouble maintaining focus, be distracted by seemingly normal stimuli, and stray from the task at hand often.</span></p>
				<p class="learning_problems_p p_0 Social-body_n pre"><em>Learning Problems:</em> This scale measures aspects related to academic achievement, such as getting poor grades and having trouble keeping up in class.<span class="learning_pro_section"></span></p>

				<p class='behavioral_p m_bt Social-body_n'><b>Behavioral Symptoms:</b> This composite is a total score that consists of Hyperactivity, Aggression, Depression, Attention, Atypicality, and Withdrawal. While the majority of these scales are discussed above, the Atypicality and Withdrawal scales are interpreted below. <span class='first_name capitalize'>the examinee</span> received scores within the <span class="behavioral_desc capitalize">average</span> range <span class="additional_del2">on this composite.</span><span class="behavioral_te_trd hide"> suggesting that these behaviors are observable across different environments.</span> <span class="behavioral_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may often shy away from <span class="his_her">his</span> peers or display odd/unusual behaviors.</span></p>
				<p class="atypicality_p p_0 Social-body_n"><em>Atypicality:</em> This scale measures the tendency to behave in ways that are considered immature, odd, or commonly associated with psychosis. <span class='first_name capitalize'>the examinee</span> received scores within the <span class="atypicality_desc capitalize">average</span> range <span class="additional_del">on this measure.</span><span class="atypicality_te_trd hide"> suggesting that these behaviors are observable across different settings.</span> <span class="atypicality_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may behave in ways that others would consider odd or inappropriate.</span></p>
				<p class="withdrawal_p p_0 Social-body_n"><em>Withdrawal:</em> This scale measures the tendency to avoid social interactions or shy away in social situations. <span class='first_name capitalize'>the examinee</span> received scores within the <span class="withdrawal_desc capitalize">average</span> range <span class="additional_del">on this measure.</span><span class="withdrawal_te_trd hide"> suggesting that these behaviors are observable across different settings.</span> <span class="withdrawal_te hide">The presence of an elevated score suggests that <span class='first_name'>the examinee</span> may be struggling with anxiety about social interactions.</span></p>
				<p class="attention2_p Social-body_n"><em>Attention Problems:</em> This scale measures distractibility and focus. <span class="first_name capitalize add_s">the examinee</span> attention was rated within the <span class="Attention2_desc">Average</span> range by <span class="Attention2_del"><span class="informer_one">the rater one</span>.</span><span class="Attention2_te hide"> The presence of an elevated score suggests that <span class="first_name">the examinee</span> may have trouble maintaining focus, be distracted by seemingly normal stimuli, and stray from the task at hand often.</span></p>
				<p class="adaptive-body_n"><b>Adaptive Behavior:</b></p>

				<p class="adaptive-body_n">Adaptive behavior includes the age-appropriate behaviors necessary for an individual to live independently and to function safely and appropriately in daily life. Adaptive behaviors include real life skills such as grooming, dressing, safety, safe food handling, school rules, ability to work, money management, cleaning, making friends, social skills, and personal responsibility.<span class="social_adaptive_checked"> In order to obtain information regarding <span class='first_name add_s'>the examinee</span> adaptive behavior<span class="hide adaptive_de"> at school and at home</span>, the Behavior Assessment System for Children, Third Edition (BASC-3) was completed by <span class="his_her">his</span> <span class="adaptive2_de"><span class="informant_one_relationship">mother</span>, <span class="informer_one">the rater one</span></span>. The BASC-3 is a multidimensional approach to evaluating the behavior of children and adolescents. It measures numerous aspects of behavior and personality.</span> Adaptive scales measure positive behaviors, so higher scores indicate more adaptive behaviors and better adjustment. <span class='first_name capitalize add_s'>the examinee</span> overall Adaptive skills were rated to be in the <span class="adaptive_desc">Average</span> range<span class="adaptive2_de1"></span>.<span class="adaptive2_de2"></span><span class="adaptive2_de_study pre"></span><span class="condition_g"></span><span class="adaptive2_de_daily"></span><span class="condition_za"></span><span class="condition_zb"></span></p>

				<p class='ch1 Social-body_n' id=''>The BASC-3 Rating Scales assess a rater's response pattern to determine how results should be interpreted.<span id="additional_de"></span></p>
			</div>
		</div>
		<!-- secount table -->
		<div class='narrative-title' style="margin-top: 25px;">
			<h1 class='text-center'><span class='text-white bold mvboli'>ClickReport's </span>BASC-3 Chart:</h1>
		</div>
		<div class='table-container-2' id='table-container-2'>

			<div class='table-header-2'>
				<h3></h3>
				<h4></h4>
			</div>
			<table id='table-cells-2'>
				<thead>
					<tr class='grey'>
						<td class="top">
							<p></p>
						</td>
						<td class="show_hide_fu top comm-2" id="informer_title_0">
							<p>0</p>
						</td>
						<td class="hide  show_hide_fu0 top comm-2 wit" id="informer_title_1">
							<p>0</p>
						</td>
						<td class="hide  show_hide_fu1 top comm-2 wit" id="informer_title_2">
							<p>0</p>
						</td>
						<td class="hide  show_hide_fu2 top comm-2 wit" id="informer_title_3">
							<p>0</p>
						</td>
						<td class="hide  show_hide_fu3 top comm-2 wit" id="informer_title_4">
							<p>0</p>
						</td>
						<!--td><p>Description of Scale</p></td-->
					</tr>
				</thead>
				<tbody class="Social-body">
					<tr class="1-2">
						<td class="w-40">
							<p>Externalizing Problems</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="externalizing_problems_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2 "><label re-attr="externalizing_problems_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="externalizing_problems_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="externalizing_problems_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="externalizing_problems_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="2-2">
						<td class="w-40">
							<p>Hyperactivity</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="hyperactivity_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="hyperactivity_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="hyperactivity_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="hyperactivity_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="hyperactivity_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="3-2">
						<td class="w-40">
							<p>Aggression</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="aggression_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="aggression_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="aggression_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="aggression_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="aggression_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="4-2 pre">
						<td class="w-40">
							<p>Conduct Problems</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="conduct_problems_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="conduct_problems_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="conduct_problems_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="conduct_problems_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="conduct_problems_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="5-2">
						<td class="w-40">
							<p>Internalizing Problems</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="internalizing_problems_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="internalizing_problems_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="internalizing_problems_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="internalizing_problems_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="internalizing_problems_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="6-2">
						<td class="w-40">
							<p>Anxiety</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="anxiety_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="anxiety_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="anxiety_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="anxiety_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="anxiety_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="7-2">
						<td class="w-40">
							<p>Depression</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="depression_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="depression_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="depression_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="depression_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="depression_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="8-2">
						<td class="w-40">
							<p>Somatization</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="somatization_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="somatization_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="somatization_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="somatization_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="somatization_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="9-2 pre">
						<td class="w-40">
							<p>School Problems</p>
						</td>
						<td class="show_hide_fu comm-2 black_out0"><label re-attr="school_problems_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2 black_out1"><label re-attr="school_problems_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="school_problems_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="school_problems_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="school_problems_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="10-2 pre">
						<td class="w-40">
							<p>Attention Problems</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="attention_problems_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="attention_problems_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="attention_problems_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="attention_problems_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="attention_problems_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="11-2 pre">
						<td class="w-40">
							<p>Learning Problems</p>
						</td>
						<td class="show_hide_fu comm-2 black_out0"><label re-attr="learning_problems_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2 black_out1"><label re-attr="learning_problems_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="learning_problems_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="learning_problems_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="learning_problems_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="12-2">
						<td class="w-40">
							<p>Atypicality</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="atypicality_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="atypicality_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="atypicality_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="atypicality_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="atypicality_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="13-2">
						<td class="w-40">
							<p>Withdrawal</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="withdrawal_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="withdrawal_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="withdrawal_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="withdrawal_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="withdrawal_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>

					<tr class="20-2" style="display: none;">
						<td class="w-40">
							<p>Attention Problems</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="Attention_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="Attention_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="Attention_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="Attention_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="Attention_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
				</tbody>
				<!--///////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->
				<tbody class="adaptive-body">
					<!-- <tr class="13-2">
						<td class="w-40">
							<p>Externalizing Problems</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="adaptive_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2 "><label re-attr="adaptive_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="adaptive_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="adaptive_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="adaptive_5">Average</label></td>
						<td><p>This index is a general estimate of overall memory</p></td>
					</tr> -->
					<tr class="14-2">
						<td class="w-40">
							<p>Adaptability</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="adaptability_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="adaptability_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="adaptability_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="adaptability_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="adaptability_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="15-2">
						<td class="w-40">
							<p>Social Skills</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="social_skills_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="social_skills_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="social_skills_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="social_skills_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="social_skills_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="16-2 pre">
						<td class="w-40">
							<p>Study Skills</p>
						</td>
						<td class="show_hide_fu comm-2 black_out0"><label re-attr="study_skills_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2 black_out1"><label re-attr="study_skills_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2 black_out2"><label re-attr="study_skills_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2 black_out3"><label re-attr="study_skills_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2 black_out4"><label re-attr="study_skills_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="17-2 pre">
						<td class="w-40">
							<p>Leadership</p>
						</td>
						<td class="show_hide_fu comm-2"><label re-attr="leadership_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2"><label re-attr="leadership_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2"><label re-attr="leadership_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2"><label re-attr="leadership_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2"><label re-attr="leadership_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="18-2">
						<td class="w-40">
							<p>Activities of Daily Living</p>
						</td>
						<td class="show_hide_fu comm-2 black_out0-2"><label re-attr="daily_living_1">Average</label></td>
						<td class="hide show_hide_fu0 comm-2 black_out1-2"><label re-attr="daily_living_2">Average</label></td>
						<td class="hide show_hide_fu1 comm-2 black_out2-2"><label re-attr="daily_living_3">Average</label></td>
						<td class="hide show_hide_fu2 comm-2 black_out3-2"><label re-attr="daily_living_4">Average</label></td>
						<td class="hide show_hide_fu3 comm-2 black_out4-2"><label re-attr="daily_living_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
					<tr class="19-2">
						<td class="w-40 bottom">
							<p>Functional Communication</p>
						</td>
						<td class="show_hide_fu bottom comm-2"><label re-attr="functional_communication_1">Average</label></td>
						<td class="hide show_hide_fu0 bottom comm-2"><label re-attr="functional_communication_2">Average</label></td>
						<td class="hide show_hide_fu1 bottom comm-2"><label re-attr="functional_communication_3">Average</label></td>
						<td class="hide show_hide_fu2 bottom comm-2"><label re-attr="functional_communication_4">Average</label></td>
						<td class="hide show_hide_fu3 bottom comm-2"><label re-attr="functional_communication_5">Average</label></td>
						<!--td><p>This index is a general estimate of overall memory</p></td-->
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<script src='/js/jquery-3.5.1.min.js'></script>
	<script src='/js/html2canvas.js'></script>


	<script type='text/javascript'>
		// Percentile lookup

		$('.show_hide_fu').addClass('td_border_right re_left_border');
		$('#include_confidence_intervals').change(function(e) {
			if ($(this).is(':checked')) {
				$('.confidence-interval').addClass('show');
			} else {
				$('.confidence-interval').removeClass('show');
			}
		});

		$('input[type="checkbox"]').change(function(e) {
			$('#table-container td').removeClass('td_border_right');
			$('#table-container-2 td').removeClass('td_border_right').addClass('com-bottom');
			if ($('#3').is(':checked')) {
				$('.table-container .show_hide_fu3').addClass('td_border_right');
				$('.table-container-2 .show_hide_fu3').removeClass('com-bottom').addClass('td_border_right');
			} else if ($('#2').is(':checked')) {
				$('.table-container .show_hide_fu2').addClass('td_border_right');
				$('.table-container-2 .show_hide_fu2').removeClass('com-bottom').addClass('td_border_right');
			} else if ($('#1').is(':checked')) {
				$('.table-container .show_hide_fu1').addClass('td_border_right');
				$('.table-container-2 .show_hide_fu1').removeClass('com-bottom').addClass('td_border_right');
			} else if ($('#0').is(':checked')) {
				$('.table-container .show_hide_fu0').addClass('td_border_right');
				$('.table-container-2 .show_hide_fu0').removeClass('com-bottom').addClass('td_border_right');
			} else {
				$('.table-container .show_hide_fu').addClass('td_border_right');
				$('.table-container-2 .show_hide_fu').removeClass('com-bottom').addClass('td_border_right');
			}

		})

		// Generate Narrative
		$('.generate-narrative').click(function(e) {
			// Get the examinee's first name
			// Check if there is a value for the examinee's first name

			// Update the ranges

			// Update the percentiles



			// Update the language delay
			if ($('#Social').is(':checked')) {
				$('.Social-body_n').addClass('hide');
			} else {
				$('.Social-body_n').removeClass('hide');
				if ($('#pre_school_h').is(':checked')) {
					$('.school_problems_p').addClass('hide');
					$('.attention2_p').removeClass('hide');

				} else {
					$('.school_problems_p').removeClass('hide');
					$('.attention2_p').addClass('hide');
				}

			}
			if ($('#adaptive').is(':checked')) {
				$('.adaptive-body_n').addClass('hide');
			} else {
				$('.adaptive-body_n').removeClass('hide');

			}


			// Hide the narrative instructions
			$('.narrative-instructions-container').hide();
			//$('.narrative-summary-instructions-container').hide();
			$('.performance-summary-instructions-container').hide();
			// Show the narrative
			$('.narrative-container').show();
			$('.narrative-summary-container').show();
			$('.performance-summary-container').show();

			//adaptive details
			var _e = [];
			_e[0] = $('[la-attr="adaptive_1"]').text();
			_e[1] = $('[la-attr="adaptability_1"]').text();
			_e[2] = $('[la-attr="social_skills_1"]').text();
			_e[3] = $('[la-attr="leadership_1"]').text();
			_e[4] = $('[la-attr="daily_living_1"]').text();
			_e[5] = $('[la-attr="functional_communication_1"]').text();
			_e[6] = $('[la-attr="study_skills_1"]').text();

			var _r = [];
			_r[0] = $('[la-attr="adaptive_2"]').text();
			_r[1] = $('[la-attr="adaptability_2"]').text();
			_r[2] = $('[la-attr="social_skills_2"]').text();
			_r[3] = $('[la-attr="leadership_2"]').text();
			_r[4] = '';
			_r[5] = $('[la-attr="functional_communication_2"]').text();
			_r[6] = $('[la-attr="study_skills_2"]').text();

			var _w = [];
			_w[0] = $('[la-attr="adaptive_3"]').text();
			_w[1] = $('[la-attr="adaptability_3"]').text();
			_w[2] = $('[la-attr="social_skills_3"]').text();
			_w[3] = $('[la-attr="leadership_3"]').text();
			_w[4] = '';
			_w[5] = $('[la-attr="functional_communication_3"]').text();
			_w[6] = $('[la-attr="study_skills_3"]').text();

			var rater_relation_one = $('#informant_one_relationship').val();
			// console.log(rater_relation_one);
			var rater_relation_two = $('#informant_two_relationship').val();
			for (let uq = 0; uq < _e.length; uq++) {
				// 1st values
				if (_e[uq] == '**') {
					_e[uq] = 'Clinically Significant';
				} else if (_e[uq] == '*') {
					_e[uq] = 'At-Risk';
				} else {
					_e[uq] = 'Average';
				}
				// 2nd values
				if (_r[uq] == '**') {
					_r[uq] = 'Clinically Significant';
				} else if (_r[uq] == '*') {
					_r[uq] = 'At-Risk';
				} else {
					_r[uq] = 'Average';
				}
				// 3rd values
				if (_w[uq] == '**') {
					_w[uq] = 'Clinically Significant';
				} else if (_w[uq] == '*') {
					_w[uq] = 'At-Risk';
				} else {
					_w[uq] = 'Average';
				}
			}
			//
			var rater_one_position = $('#informant_one_relationship [value="' + rater_relation_one + '"]').attr('ch_attr');
			var rater_two_position = $('#informant_two_relationship [value="' + rater_relation_two + '"]').attr('ch_attr');

			//
			if (_e[0] == _e[1] && _e[2] == _e[3] && _e[0] == _e[3] && _e[5] == _e[0] && _e[0] != 'Average') {
				$('.adaptive2_de2').html(' More specifically, <span class="first_name add_s">the examinee</span> <span class="informant_one_relationship">mother</span> rated the areas of Adaptability, Social Skills,<span class="pre_n"> Leadership,</span> and Functional Communication to be within the <span class="adaptive_desc">Average</span> range. ');
			} else {
				$('.adaptive2_de2').html(' More specifically, <span class="first_name add_s">the examinee</span> <span class="informant_one_relationship">mother</span> rated the area of Adaptability to be within the <span class="adaptability_desc">Average</span> range, the area of Social Skills to be within the <span class="social_skills_desc">Average</span> range,<span class="pre_n"> the area of Leadership to be within the <span class="leadership_desc">Average</span> range,</span> and the area of Functional Communication to be within the <span class="functional_com_desc">Average</span> range. ');
			}

			if (_e[4] != 'Average') {
				$('.adaptive2_de_daily').html('Additionally, <span class="informer_one">the rater one</span> rated <span class="first_name add_s">the examinee</span> daily living skills to be within the <span class="daily_living_desc">Average</span> range.');
			} else {
				$('.adaptive2_de_daily').text('');
			}

			if ($('#0').is(':checked') || $('#1').is(':checked') || $('#2').is(':checked') || $('#3').is(':checked')) {
				$('.adaptive_de').removeClass('hide');
				// condition A
				if ($('#1').is(':checked') || $('#2').is(':checked') || $('#3').is(':checked')) {
					$('.adaptive2_de').html(' <span class="informant_one_relationship">mother</span> (<span class="informer_one">the rater one</span>), <span class="his_her">his</span> <span class="informant_tow_relationship">general education teacher</span> (<span class="informer_tow">the rater two</span>) and <span class="his_her">his</span> <span class="informant_three_relationship">general education teacher</span> (<span class="informant_three">the rater three</span>)');
					//condition za
					$('.condition_za').html(' <span class="informant_three capitalize">the rater three</span>, <span class="first_name add_s">the examinee</span> <span class="informant_three_relationship"></span>');
					//condition zb
					if (_w[0] == 'Average' && _w[1] == 'Average' && _w[2] == 'Average' && _w[6] == 'Average' && _w[4] == 'Average' && _w[5] == 'Average') {
						$('.condition_zb').text(', indicated Average functioning across all domains of adaptability.');
					} else {
						$('.condition_zb').html(', indicated functioning within the ' + _w[0] + ' range for overall adaptability. More specifically, <span class="informant_three">the rater three</span> reported <span class="first_name add_s">the examinee</span> ability to adapt to changes within the ' + _w[1] + ' range, <span class="his_her">his</span> ability to interact with peers within the ' + _w[2] + ' range, <span class="his_her">his</span> ability to utilize effective Study Skills within the ' + _w[6] + ' range, and <span class="his_her">his</span> ability to lead a group effectively within the ' + _w[3] + ' range. Additionally, <span class="informant_three">the rater three</span> reported <span class="first_name add_s">the examinee</span> ability to communicate effectively at school within the ' + _w[5] + ' range.');
					}
				} else {
					$('.adaptive2_de').html(' <span class="informant_one_relationship">mother</span>, <span class="informer_one">the rater one</span> and <span class="his_her">his</span> <span class="informant_tow_relationship">general education teacher</span>, <span class="informer_tow">the rater two</span>');
					//condition za
					$('.condition_za').text('');
					//condition zb
					$('.condition_zb').text('');
				}
				// condition d

				if (_e[0] == _r[0]) {
					$('.adaptive2_de1').html(' by both <span class="his_her">his</span> <span class="informant_tow_relationship"></span> and <span class="informant_one_relationship"></span>. This would suggest that <span class="first_name add_s">the examinee</span> overall Adaptive skills are consistent across settings');
				} else {
					$('.adaptive2_de1').html(' by <span class="his_her">his</span> <span class="informant_one_relationship"></span> and within the ' + _r[0] + ' range by <span class="his_her">his</span> <span class="informant_tow_relationship"></span>, suggesting that <span class="his_her">his</span> overall Adaptive skills vary based on <span class="his_her">his</span> environment');
				}

				// condition e
				if ($('#pre_school_h').is(':checked')) {
					var condition_ch = _e[0] == _e[1] && _e[0] == _e[2] && _e[0] == _e[5] && _e[0] != 'Average';
				} else {
					var condition_ch = _e[0] == _e[1] && _e[0] == _e[2] && _e[0] == _e[3] && _e[0] == _e[5] && _e[0] != 'Average';
				}

				if (_e[0] == _e[1] && _e[0] == _e[2] && _e[0] == _e[3] && _e[0] == _e[5] && _e[0] == _r[0] && _e[0] == _r[1] && _e[0] == _r[2] && _e[0] == _r[3] && _e[0] == _r[5] && _e[0] != 'Average') {
					$('.adaptive2_de2').html(' More specifically, <span class="informer_one">the rater one</span> and <span class="informer_tow">the rater one</span> both reported <span class="adaptability_desc"></span> concerns for Adaptability, Social Skills, <span class="pre_n">Leadership,</span> and Functional Communication.');
				} else if (condition_ch) {
					$('.adaptive2_de2').html(' More specifically, <span class="first_name add_s">the examinee</span> <span class="informant_one_relationship">mother</span> rated the areas of Adaptability, Social Skills,<span class="pre_n"> Leadership,</span> and Functional Communication to be within the <span class="adaptive_desc">Average</span> range.');
				} else {
					$('.adaptive2_de2').html(' More specifically, <span class="first_name add_s">the examinee</span> <span class="informant_one_relationship">mother</span> rated the area of Adaptability to be within the <span class="adaptability_desc"></span> range, the area of Social Skills to be within the <span class="social_skills_desc"></span> range,<span class="pre_n"> the area of Leadership to be within the <span class="leadership_desc">Average</span> range,</span> and the area of Functional Communication to be within the <span class="functional_com_desc"></span> range.');
				}

				// condition study

				if ($('#pre_school_h').is(':checked')) {
					$('.adaptive2_de_study').text('');
				} else {
					if (_r[6] != 'Average' && rater_one_position != 'teacher' || rater_two_position != 'teacher') {
						// $('.adaptive2_de_study').html(' Additionally, <span class="informer_tow">the rater two</span> rated the area of Study Skills to be within the ' + _r[6] + ' range.');
						$('.adaptive2_de_study').html('');
					} else {
						$('.adaptive2_de_study').html(' Additionally, <span class="informer_one">the rater one</span> rated the area of Study Skills to be within the ' + _e[6] + ' range, and <span class="informer_tow">the rater two</span> rated ' + _r[6] + ' concerns for study skills.');
					}
					console.log(_e[6]);
				}
				//condition g
				if (_r[1] == _r[2] && _r[1] == _r[3] && _r[1] == _r[5] && _r[1] != 'Average') {
					$('.condition_g').html(' Furthermore, <span class="first_name add_s">the examinee</span> ' + rater_relation_two + ' rated the areas of Adaptability, Social Skills,<span class="pre_n"> Leadership,</span> and Functional Communication to be within the ' + _r[1] + ' range.');
				} else {
					$('.condition_g').html(' Furthermore, <span class="first_name add_s">the examinee</span> ' + rater_relation_two + ' rated the area of Adaptability to be within the ' + _r[1] + ' range, the area of Social Skills to be within the ' + _r[2] + ' range,<span class="pre_n"> the area of Leadership to be within the ' + _r[3] + ' range,</span> and the area of Functional Communication to be within the ' + _r[5] + ' range.');
				}

				//condition daily
				$('.adaptive2_de_daily').text('');

			} else {
				$('.adaptive2_de1').text('');
				$('.condition_g').text('');
				$('.condition_za').text('');
				$('.condition_zb').text('');
				$('.adaptive2_de_study').text('');
				$('.adaptive_de').addClass('hide');
				$('adaptive2_de').html('<span class="informant_one_relationship">mother</span>, <span class="informer_one">the rater one</span>');

			}


			$('.adaptive_desc').text(_e[0]);
			$('.adaptability_desc').text(_e[1]);
			$('.social_skills_desc').text(_e[2]);
			$('.leadership_desc').text(_e[3]);
			$('.functional_com_desc').text(_e[5]);
			$('.daily_living_desc').text(_e[4]);
			//social details
			var informant_one = $('#informant_one').val();
			var informant_two = $('#informant_two').val();
			var informant_three = $('#informant_three').val();
			var informant_o_relation = $('#informant_one_relationship').val();
			var informant_t_relation = $('#informant_two_relationship').val();
			var informant_tr_relation = $('#informant_three_relationship').val();

			var dec_val = [];
			var dec_cls = [];
			dec_val[0] = $('#externalizing_problems_1').val();
			dec_cls[0] = 'externalizing';
			dec_val[1] = $('#hyperactivity_1').val();
			dec_cls[1] = 'hyperactivity';
			dec_val[2] = $('#aggression_1').val();
			dec_cls[2] = 'aggression';
			dec_val[3] = $('#conduct_problems_1').val();
			dec_cls[3] = 'conduct';
			dec_val[4] = $('#internalizing_problems_1').val();
			dec_cls[4] = 'internalizing';
			dec_val[5] = $('#anxiety_1').val();
			dec_cls[5] = 'anxiety';
			dec_val[6] = $('#depression_1').val();
			dec_cls[6] = 'depression';
			dec_val[7] = $('#somatization_1').val();
			dec_cls[7] = 'somatization';
			dec_val[8] = $('#school_problems_1').val();
			dec_cls[8] = 'school_problems';
			dec_val[9] = $('#attention_problems_1').val();
			dec_cls[9] = 'attention';
			dec_val[10] = $('#learning_problems_1').val();
			dec_cls[10] = 'learning_problems';
			dec_val[11] = $('#behavioral_symptoms_1').val();
			dec_cls[11] = 'behavioral';
			dec_val[12] = $('#atypicality_1').val();
			dec_cls[12] = 'atypicality';
			dec_val[13] = $('#withdrawal_1').val();
			dec_cls[13] = 'withdrawal';
			dec_val[14] = $('#Attention_1').val();
			dec_cls[14] = 'Attention2';

			var sec_val = $('.Social-body .show_hide_fu0 input').map(function() {
				return $(this).val();
			}).get();
			var thr_val = $('.Social-body .show_hide_fu1 input').map(function() {
				return $(this).val();
			}).get();

			///////////////////////////////////////////////////////////////////////////////////

			// val.length);
			var fst_text = $('.Social-body .show_hide_fu lable').map(function() {
				return $(this).text();
			}).get();

			var sec_text = $('.Social-body .show_hide_fu0 lable').map(function() {
				return $(this).text();
			}).get();

			var trd_text = $('.Social-body .show_hide_fu1 lable').map(function() {
				return $(this).text();
			}).get();
			console.log("f" + fst_text);
			console.log("s" + sec_text);
			console.log("th" + trd_text);

			var one_desc = $('.Social-body .show_hide_fu lable').map(function() {
				return $(this).attr('attr');
			}).get();

			var sec_desc = $('.Social-body .show_hide_fu0 lable').map(function() {
				return $(this).attr('attr');
			}).get();

			var trd_desc = $('.Social-body .show_hide_fu1 lable').map(function() {
				return $(this).attr('attr');
			}).get();

			// console.log(sec_desc);
			// console.log(sec_desc);

			var thr_sc_desc = $('lable[la-attr="school_problems_3"]').attr('attr');
			var thr_lea_desc = $('lable[la-attr="learning_problems_3"]').attr('attr');
			var inf_one = $('input[attr_title="informer_title0"]').val();
			var inf_two = $('input[attr_title="informer_title1"]').val();
			var inf_thr = $('input[attr_title="informer_title2"]').val();

			if (inf_one == '') {
				inf_one = 'the rater one';
			}
			if (inf_two == '') {
				inf_two = 'the rater two';
			}
			if (informant_three == '') {
				inf_thr = 'the rater three';
			}
			//Changes in the narrative school problem
			var sc_pro_ae = '';
			var sc_pro_af = '';
			var le_pro_ae = '';
			var le_pro_af = '';
			if (rater_one_position == 'teacher') {
				var sc_pro_ad = " <span class='first_name capitalize add_s'>the examinee</span> " + informant_o_relation + " reported concerns within the <span class='school_problems_desc'>Average</span> range for overall school problems.";
				//changes in the learning problem
				var le_pro_ad = " <span class='first_name capitalize add_s'>the examinee</span> " + informant_o_relation + " reported concerns within the <span class='learning_problems_desc'>Average</span> range for overall learning problems.";
			} else {
				var sc_pro_ad = " An overall composite for school problems is only obtained when the BASC-3 is completed by a teacher/educator.";
				var le_pro_ad = ' A score for learning problems is only obtained when the BASC-3 is completed by a teacher/educator.';
			}

			if ($('#0:checked').length > 0 == true || $('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {
				// $('.second_check_box_ac').addClass('hide');

				//school problem changes in narative
				if (rater_one_position == 'teacher' && rater_two_position == 'teacher') {

					sc_pro_ad = ' <span class="first_name capitalize add_s">the examinee</span> ' + informant_o_relation + ' reported concerns within the <span class="school_problems_desc">Average</span> range for overall school problems and ' + inf_two + ' reported ' + sec_desc[8] + ' functioning.';
					le_pro_ad = " <span class='first_name capitalize add_s'>the examinee</span> " + informant_o_relation + " reported concerns within the <span class='learning_problems_desc'>Average</span> range for learning problems and " + inf_two + " reported " + sec_desc[10] + " functioning.";

				} else {
					if (rater_two_position == 'teacher') {
						sc_pro_ae = " <span class='first_name capitalize add_s'>the examinee</span> " + informant_t_relation + " reported concerns within the " + sec_desc[8] + " range for overall school problems.";
						le_pro_ae = " <span class='first_name capitalize add_s'>the examinee</span> " + informant_t_relation + " reported concerns within the " + sec_desc[10] + " range for learning problems.";
					}
				}

				/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				if ($('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {
					sc_pro_af = " Additionally, " + inf_thr + " reported concerns within the " + trd_desc[8] + " range on this composite.";
					le_pro_af = " Additionally, " + inf_thr + " reported concerns within the " + trd_desc[10] + " range on this measure.";
					$('.additional_informers').html(' (<span class="informer_one">the rater one</span>), ' + informant_t_relation + ' (' + inf_two + '), and  ' + informant_tr_relation + '  (' + inf_thr + ').');

				} else {

					$('.additional_informers').html(', <span class="informer_one">the rater one</span> and ' + informant_t_relation + ', ' + inf_two + '.');

				}
				for (let t = 0; t < 14; t++) {
					if ($('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {
						console.log(one_desc[t] == 'Average' && trd_desc[t] == 'Average' && sec_desc[t] == 'Average');
						if (one_desc[t] == 'Average' && trd_desc[t] == 'Average' && sec_desc[t] == 'Average') {
							var infroment_last_text = ',';
						} else {
							var infroment_last_text = '.';
						}
						if (dec_cls[t] == 'somatization' || dec_cls[t] == 'depression' || dec_cls[t] == 'anxiety' || dec_cls[t] == 'conduct') {
							infroment_last_text = ' on this measure.';
						} else if (dec_cls[t] == 'withdrawal' || dec_cls[t] == 'atypicality' || dec_cls[t] == 'behavioral') {
							infroment_last_text = ' on this measure.';
						} else if (dec_cls[t] == 'attention') {
							infroment_last_text = ' on measures of attention,';
						}

						// cls[t] == 'withdrawal');
						if (fst_text[t] == sec_text[t] && fst_text[t] == trd_text[t]) {
							$('.' + dec_cls[t] + '_p .additional_del').text('by all evaluators' + infroment_last_text);
							$('.' + dec_cls[t] + '_p .additional_del1').text('by all evaluators' + infroment_last_text);
							$('.' + dec_cls[t] + '_p .additional_del2').text('by all evaluators' + infroment_last_text);
						} else if (fst_text[t] != sec_text[t] || fst_text[t] != trd_text[t]) {
							$('.' + dec_cls[t] + '_p .additional_del').html('by ' + inf_one + ', within the ' + sec_desc[t] + ' range by ' + inf_two + ', and within the ' + trd_desc[t] + ' range by ' + inf_thr + '.');
							$('.' + dec_cls[t] + '_p .additional_del1').html('by ' + inf_one + ', within the ' + sec_desc[t] + ' range by ' + inf_two + ', and within the ' + trd_desc[t] + ' range by ' + inf_thr + '.');
							$('.' + dec_cls[t] + '_p .additional_del2').html('by ' + inf_one + ', within the ' + sec_desc[t] + ' range by ' + inf_two + ', and within the ' + trd_desc[t] + ' range by ' + inf_thr + '.');
						}
					} else {
						if (fst_text[t] == sec_text[t]) {
							$('.' + dec_cls[t] + '_p .additional_del').text('by both ' + inf_one + ' and ' + inf_two + '.');
							$('.' + dec_cls[t] + '_p .additional_del1').text('by both ' + inf_one + ' and ' + inf_two + '.');
							$('.' + dec_cls[t] + '_p .additional_del2').text('by both ' + inf_one + ' and ' + inf_two + '.');
						} else if (fst_text[t] != sec_text[t]) {
							$('.' + dec_cls[t] + '_p .additional_del').html('by ' + inf_one + ' <span class="chn_but">but</span> within the ' + sec_desc[t] + ' range by ' + inf_two + '.');
							$('.' + dec_cls[t] + '_p .additional_del1').html('by ' + inf_one + ' <span class="chn_but">but</span> within the ' + sec_desc[t] + ' range by ' + inf_two + '.');
							$('.' + dec_cls[t] + '_p .additional_del2').html('by ' + inf_one + ' <span class="chn_but">but</span> within the ' + sec_desc[t] + ' range by ' + inf_two + '.');
						}

					}

				}
			} else {
				$('.additional_del').text('on this measure.');
				$('.additional_del1').html('by <span class="informer_one">the rater one</span>.');
				$('.additional_del2').text('on this composite.');
				$('.additional_informers').text('.');
				// $('.second_check_box_ac').removeClass('hide');
				$('.additional_informers').html(', <span class="informer_one">the rater one</span>.');

			}

			$('.school_pro_section').html(sc_pro_ad + sc_pro_ae + sc_pro_af);
			$('.learning_pro_section').html(le_pro_ad + le_pro_ae + le_pro_af);

			var f = [];
			var o = [];

			f[0] = $('#f_index').val();
			f[1] = $('#Response_Pattern').val();
			f[2] = $('#Consistency').val();

			o[0] = $('#f_index1').val();
			o[1] = $('#Response_Pattern1').val();
			o[2] = $('#Consistency1').val();

			var acc_ary = [];
			var con_ary = [];
			var acc_ary1 = [];
			var con_ary1 = [];
			for (let y = 0; y < 3; y++) {

				if (f[y] == 'acceptable') {
					acc_ary.push(y);
				} else {
					con_ary.push(y);
				}
				if (o[y] == 'acceptable') {
					acc_ary1.push(y);
				} else {
					con_ary1.push(y);
				}
			}
			var acc_count = acc_ary.length;
			var con_count = con_ary.length;
			var acc_count1 = acc_ary1.length;
			var con_count1 = con_ary1.length;
			// first row
			// console.log(acc_ary);
			if (acc_count == 3) {
				bb = " <span class='informer_one capitalize add_s'>the rater one</span> rating consistency, response pattern, and F-Index were considered to be acceptable, indicating that these ratings are likely a valid representation of <span class='first_name add_s'>the examinee</span> current behavior/social-emotional functioning.";
			} else if (con_count == 3) {
				if (f[0] == 'extreme caution' && f[1] == 'extreme caution' && f[2] == 'extreme caution') {
					var caution_val = 'extreme caution';
				} else if (f[0] == 'extreme caution' && f[1] == 'caution' && f[2] == 'extreme caution' || f[0] == 'extreme caution' && f[1] == 'extreme caution' && f[2] == 'caution') {
					var caution_val = 'extreme caution';
				} else {
					var caution_val = 'caution';
				}
				bb = " <span class='informer_one capitalize add_s'>the rater one</span> rating consistency, response pattern, and F-Index indicated that the results of these ratings should be interpreted with " + caution_val + ", and are likely not a valid representation of <span class='first_name add_s'>the examinee</span> current behavior/social-emotional functioning.";
			} else if (acc_count == 2) {
				if (acc_ary[0] == 0) {
					var acc_point = '';
					var acc_point2 = '';
					var valu = '';
					if (con_ary[0] == 2) {
						acc_point = 'appropriate response pattern';
						acc_point2 = 'consistency';
						valu = f[2];
					} else {
						acc_point2 = 'response pattern';
						acc_point = 'consistency';
						valu = f[1];
					}
					console.log(con_ary[0]);
					bb = " While <span class='informer_one add_s'>the rater one</span> ratings were considered acceptable for the F-Index and " + acc_point + ", the " + acc_point2 + " index indicates that these scores should be interpreted with " + valu + ".";
				} else {
					var check_box_data = f[0].replace('_', ' ');
					bb = " While <span class='informer_one add_s'>the rater one</span> ratings were considered acceptable for appropriate response pattern and consistency, the F-index scale indicates responses that were excessively negative, and within the " + check_box_data + " range.";
				}
			} else if (acc_count == 1) {
				if (acc_ary[0] == 0) {
					var check_box_data = f[1].replace('_', ' ');
					var check_box_data1 = f[2].replace('_', ' ');
					bb = " While <span class='informer_one add_s'>the rater one</span> ratings were considered acceptable for the F-Index, the response pattern index indicates scores should be interpreted with " + check_box_data + ". Likewise, the consistency index indicates scores that should be interpreted with " + check_box_data1 + ". Due to these factors, <span class='informer_one add_s'>the rater one</span> ratings are likely not a valid estimate of <span class='first_name add_s'>the examinee</span> current behavior/social-emotional functioning.";
				} else if (acc_ary[0] == 1) {
					var check_box_data1 = f[2].replace('_', ' ');
					bb = " While <span class='informer_one capitalize add_s'>the rater one</span> ratings were considered acceptable for the response pattern index, the F-Index yielded results that would indicate overly negative responses. Likewise, the consistency index yielded results that should be interpreted with " + check_box_data1 + ".";
				} else {
					var check_box_data = f[1].replace('_', ' ');
					bb = " While <span class='informer_one add_s'>the rater one</span> ratings were considered acceptable for the consistency index, the F-Index yielded results that would indicate an overly negative response pattern and the response pattern index indicates results that should be interpreted with " + check_box_data + ".";
				}
			}
			$('#additional_de').html(bb);
			att_text = "<span class='informer_one'>the rater one</span>.";
			// 2nd row
			if ($('#0:checked').length > 0 == true || $('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {
				var Att_1 = $('lable[la-attr="Attention_1"]').text();
				var Att_2 = $('lable[la-attr="Attention_2"]').text();
				var Att_3 = $('lable[la-attr="Attention_3"]').text();

				if (Att_2 == '**') {
					$('.Attention2_te').removeClass('hide');
					att_range = 'Clinically Significant';
				} else if (Att_2 == '*') {
					$('.Attention2_te').removeClass('hide');
					att_range = 'At-Risk';
				} else {
					$('.Attention2_te').addClass('hide');
					att_range = 'Average';
				}
				if (Att_3 == '**') {
					$('.Attention2_te').removeClass('hide');
					att_range1 = 'Clinically Significant';
				} else if (Att_3 == '*') {
					$('.Attention2_te').removeClass('hide');
					att_range1 = 'At-Risk';
				} else {
					$('.Attention2_te').addClass('hide');
					att_range1 = 'Average';
				}

				if ($('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {

					if (Att_1 == Att_2 && Att_2 == Att_3) {
						att_text = 'all evaluators on measures of attention,';
					} else {
						att_text = "<span class='informer_one'>the rater one</span>, within the " + att_range + " range by <span class='informer_tow'>the rater two</span>, and within the " + att_range1 + " range by <span class='informant_three'>the rater three</span>.";
					}
				} else {
					if (Att_1 == Att_2) {
						att_text = "both <span class='informer_one'>the rater one</span> and <span class='informer_tow'>the rater two</span>.";
					} else {
						// range);
						att_text = "<span class='informer_one'>the rater one</span> but within the " + att_range + " range by <span class='informer_tow'>the rater two</span>.";
					}
				}
				var be = '';
				var bf = '';
				var bg = '';
				var bh = '';
				var bi = '';
				if (acc_count == acc_count1) {
					be = " Likewise,";
					// bg = '.';
				} else {
					be = " Additionally,";
					// bg = " indicating that these ratings are likely a valid representation of <span class='first_name add_s'>the examinee</span> current behavior/social-emotional functioning.";
				}
				if (acc_count1 == 3) {
					bf = " <span class='informer_tow capitalize add_s'>the rater two</span> rating consistency, response pattern, and F-Index were considered to be acceptable.";
				} else if (con_count1 == 3) {
					if (o[0] == 'extreme caution' && o[1] == 'extreme caution' && o[2] == 'extreme caution') {
						var caution_val = 'extreme caution';
					} else if (o[0] == 'extreme caution' && o[1] == 'caution' && o[2] == 'extreme caution' || o[0] == 'extreme caution' && o[1] == 'extreme caution' && o[2] == 'caution') {
						var caution_val = 'extreme caution';
					} else {
						var caution_val = 'caution';
					}
					bf = " <span class='informer_tow capitalize add_s'>the rater two</span> rating consistency, response pattern, and F-Index indicated that the results of these ratings should be interpreted with " + caution_val + ", and are likely not a valid representation of <span class='first_name add_s'>the examinee</span> current behavior/social-emotional functioning.";
				} else if (acc_count1 == 2) {
					if (acc_ary1[0] == 0) {
						var acc_point = '';
						var acc_point2 = '';
						var valu = '';
						if (con_ary1[0] == 1) {
							acc_point2 = 'response pattern';
							acc_point = 'consistency';
							valu = o[1];
						} else {
							acc_point = 'appropriate response pattern';
							acc_point2 = 'consistency';
							valu = o[2];
						}

						bf = " <span class='informer_tow add_s'>the rater two</span> ratings were considered acceptable for the F-Index and " + acc_point + ", but the " + acc_point2 + " index indicates that these scores should be interpreted with " + valu + ".";
					} else {

						var check_box_data = o[0].replace('_', ' ');
						bf = " <span class='informer_tow capitalize add_s'>the rater two</span> ratings were considered acceptable for appropriate response pattern and consistency; but the F-index scale indicates responses that were excessively negative, and within the " + check_box_data + " range.";
					}
				} else if (acc_count1 == 1) {
					if (acc_ary1[0] == 0) {
						var check_box_data = o[1].replace('_', ' ');
						var check_box_data1 = o[2].replace('_', ' ');
						bf = " <span class='informer_tow capitalize add_s'>the rater two</span> ratings were considered acceptable for the F-Index, the response pattern index indicates scores should be interpreted with " + check_box_data + ". Likewise, the consistency index indicates scores that should be interpreted with " + check_box_data1 + ". Due to these factors, <span class='informer_tow add_s'>the rater two</span> ratings are likely not a valid estimate of <span class='first_name add_s'>the examinee</span> current behavior/social-emotional functioning.";
					} else if (acc_ary1[0] == 1) {
						bf = '';
						be = '';
						// $('#additional_de').text("<span class='first_name add_s'>the examinee</span>'s rating consistency, response pattern, and F-Index were considered to be acceptable, indicating that these ratings are likely a valid representation of <span class='first_name add_s'>the examinee</span>'s current behavior/social-emotional functioning.    The consistency index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way.");
					} else {
						var check_box_data = o[1].replace('_', ' ');
						bf = " <span class='informer_tow capitalize add_s'>the rater two</span> ratings were considered acceptable for the consistency index, the F-Index yielded results that would indicate an overly negative response pattern and the response pattern index indicates results that should be interpreted with " + check_box_data + ".";
					}
				}
				if (o[1] != 'acceptable' || f[1] != 'acceptable') {
					bg = ' The response pattern index identifies when the rater has used a pattern to mark answers (i.e. S,A,N,S,A,N,S,A,N).';
				}
				if (o[2] != 'acceptable' || f[2] != 'acceptable') {
					bh = ' The consistency index identifies situations when the rater has given inconsistent responses to items that are typically answered in a similar way.';
				}
				var tr_f_index = $('#f_index2').val();
				var tr_Response_Pattern = $('#Response_Pattern2').val();
				var tr_Consistency = $('#Consistency1').val();

				if ($('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {
					if (tr_f_index == tr_Response_Pattern && tr_Response_Pattern == tr_Consistency && tr_Response_Pattern == 'acceptable') {
						bi = ' Additionally, <span class="informant_three add_s">the rater three</span> answers were considered acceptable for F-index, response pattern, and consistency.';
					} else {
						bi = ' Additionally, <span class="informant_three add_s">the rater three</span> answers were in the ' + tr_f_index + ' range for F-index, ' + tr_Response_Pattern + ' range for response pattern, and within the ' + tr_Consistency + ' range for consistency.';
					}
					$('#additional_de3').removeClass('hide');
				}
				$('#additional_de').html(bb + be + bf + bg + bh + bi);
			}

			///////////////////////////////////////////////////////////////////////////////////
			for (let p = 0; p < dec_val.length; p++) {
				// cls[p]);
				if (dec_val[p] > 69 || sec_val[p] > 69 || thr_val[p] > 69) {
					$('.' + dec_cls[p] + '_te').removeClass('hide');
				} else if (dec_val[p] > 59 || sec_val[p] > 59 || thr_val[p] > 59) {
					$('.' + dec_cls[p] + '_te').removeClass('hide');
				} else {
					$('.' + dec_cls[p] + '_te').addClass('hide');
				}
				if (dec_val[p] > 69) {
					$('.' + dec_cls[p] + '_desc').text('clinically significant');
				} else if (dec_val[p] > 59) {
					$('.' + dec_cls[p] + '_desc').text('at-risk');
				} else {
					$('.' + dec_cls[p] + '_desc').text('average');
				}
				if ($('#1:checked').length > 0 == true || $('#2:checked').length > 0 == true || $('#3:checked').length > 0 == true) {
					if (dec_val[p] < 60 && sec_val[p] < 60 && thr_val[p] < 60) {
						$('.' + dec_cls[p] + '_te_trd').removeClass('hide');
						$('.' + dec_cls[p] + '_te').addClass('hide');
					} else {
						$('.' + dec_cls[p] + '_te_trd').addClass('hide');
					}
				}
			}
			///////////////////////////////////////////////////////////////////////////////////

			$('.Attention2_del').html(att_text);
			// Update the examinee name
			if (informant_one != '') {
				$('.informer_one').text(informant_one);
				$('.informer_one').addClass('capitalize');

			}
			if (informant_o_relation != '') {
				$('.informant_one_relationship').text(informant_o_relation);
			}
			if (informant_two != '') {
				$('.informer_tow').text(informant_two);
				$('.informer_tow').addClass('capitalize');

			}
			if (informant_t_relation != '') {
				$('.informant_tow_relationship').text(informant_t_relation);
			}
			if (informant_three != '') {
				$('.informant_three').text(informant_three);
				$('.informant_three').addClass('capitalize');

			}
			if (informant_tr_relation != '') {
				$('.informant_three_relationship').text(informant_tr_relation);

			}
			if ($('#pre_school_h').is(':checked')) {
				$('.pre_n').addClass('hide');
				$('.pre').addClass('hide');
			}
			// Update the gender references
			if ($('#gender').val() == 'female') {
				$('.his_her').text('her');
				$('.him_her').text('her');
				$('.he_she').text('she');
				$('.was_were').text('was');
			} else if ($('#gender').val() == 'neutral') {
				$('.his_her').text('their');
				$('.him_her').text('them');
				$('.he_she').text('they');
				$('.was_were').text('were');
			} else {
				$('.his_her').text('his');
				$('.him_her').text('him');
				$('.he_she').text('he');
				$('.was_were').text('was');
			}
			var examinee_name = $('#first_name').val();
			if ($.trim(examinee_name) !== '') {
				//examinee_name = 'the examinee';
				$('.first_name').text(examinee_name);
				$('.first_name').addClass('capitalize');

			}
			// if ($('#pre_school_h:checked').length > 0 == true) {
			// 	$('.attention2_p').removeClass('hide');
			// } else {
			// 	$('.attention2_p').addClass('hide');
			// }
			var map = $('.add_s').map(function() {
				return $(this).text($(this).text().replace("'s", '') + "'s");
			}).get();
		});



		$('.copy-narrative').click(function(e) {
			var urlField = document.getElementById('narrative-container');
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField);
			window.getSelection().addRange(range);
			document.execCommand('copy');
		});

		function getNewHTML() {
			return $('#table-container');
		}


		$('.copy-table').click(function() {
			// Hide input fields
			$('#table-container input').hide();
			// Update field values
			for (let u = 0; u < 15; u++) {
				$('#table-container .' + u + ' .total_memoryindex_ci').text($('#table-container .' + u + ' .confidence-interval input').val());
				$('#table-container table tr').addClass('score_td');
				$('#table-cells tr td p').attr('style', 'font-size:10px;');
				// $('#table-container').attr('style', 'margin-top: 75px;');

				$('#table-container .text-values').show();

				$('#table-container').attr('style', 'min-height:5px;');
			}

			$('.grey').attr('style', 'background-color:gray');

			// $('#table-container table tr').addClass('score_td');
			// $('#table-cells tr td p').attr('style', 'font-size:10px;');

			// $('#table-container .text-values').show();

			// $('#table-container').attr('style', 'min-height:5px;');

			var newest_html = getNewHTML()[0];


			var urlField = document.getElementById('table-container');
			// urlField.getRow(0).setMinimumHeight(35);

			// aa.setMinimumHeight(35);
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField)
			window.getSelection();
			window.getSelection().addRange(range);
			document.execCommand('copy');

			// Show input fields again
			$('#table-container input').show();
			// Hide field values as text again
			$('#table-container .text-values').hide();
			window.getSelection().removeAllRanges();

			$('#table-cells tr td p').removeAttr('style', 'font-size:10px;');
			$('#table-container').removeAttr('style', 'min-height:5px;');
			$('#table-container').attr('style', 'margin-top: 75px;');
			$('#table-container table tr').removeClass('score_td');
			// Show success message
			$('.copied-table').show();
			// Hide success message after 3 seconds
			setTimeout(function() {
				$('.copied-table').fadeOut();
			}, 3000);
		});

		// copy Graphs table
		$('.copy-graphs-table').click(function() {

			var newest_html = getNewHTML()[0];
			// st_html);

			var urlField = document.getElementById('table-container-2');
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField);
			window.getSelection().addRange(range);
			document.execCommand('copy');


			window.getSelection().removeAllRanges();

			// Show success message
			$('.copied-table').show();
			// Hide success message after 3 seconds
			setTimeout(function() {
				$('.copied-table').fadeOut();
			}, 3000);
		});

		$('input.informer_name').on('change', function() {
			var title_cls = $(this).attr('attr_title');
			var title = $(this).val();
			if (title == '') {
				title = '0';
			}
			$('#' + title_cls).text(title);
			// e_cls);
			//secount table
			var secount_table_title = title_cls.replace('0', '_0');
			secount_table_title = secount_table_title.replace('1', '_1');
			secount_table_title = secount_table_title.replace('2', '_2');
			secount_table_title = secount_table_title.replace('3', '_3');
			secount_table_title = secount_table_title.replace('4', '_4');
			$('#' + secount_table_title).text(title);

			var table_title = title_cls.replace('0', '_t0');
			table_title = table_title.replace('1', '_t1');
			table_title = table_title.replace('2', '_t2');
			table_title = table_title.replace('3', '_t3');
			table_title = table_title.replace('4', '_t4');
			$('#' + table_title).text(title);
		});

		$('#first_name').on('change', function() {
			var name = $(this).val();

			$('#additional_title').text(name);
			$('#first_row').text(name);
		});

		$('#show-rows-1 .show_hide_fu').on('change', function() {
			var id = $(this).attr('id');
			var last = $('#show-rows-1 .show_hide_fu').map(function() {
				this.id
			});


			last_id = last.prevObject.length - 1;

			active_id = '';
			var e = 0;
			var deactive_id = [];
			for (let yu = 0; yu < 4; yu++) {
				if ($('#' + yu + ':checked').length > 0 == true) {
					var active_id = yu;
				} else {
					deactive_id[e] = yu;
					e++;
				}
			}

			var atLeastOneIsChecked = $('#' + id + ':checked').length > 0;
			if (atLeastOneIsChecked) {
				for (let i = 0; i <= active_id; i++) {
					$('.show_hide_fu' + i).removeClass('hide');
				}

			} else {
				for (let i = 0; i < deactive_id.length; i++) {
					if (active_id < deactive_id[i] || active_id == '') {

						$('.show_hide_fu' + deactive_id[i]).addClass('hide');

					}
				}
			}
			// secount table

			var cu_id = $(this).attr("id");

			var atLeastOneIsChecked0 = $("#0:checked").length > 0;
			var atLeastOneIsChecked1 = $("#1:checked").length > 0;
			var atLeastOneIsChecked2 = $("#2:checked").length > 0;
			var atLeastOneIsChecked3 = $("#3:checked").length > 0;

			if (atLeastOneIsChecked3) {

				$(".table-container-2").css({
					"width": "100%",
					"margin-left": "0px"
				});
				$('.w-40').attr('style', 'width: 25% !important');
				$('.comm-2').css('width', '15%');
			} else if (atLeastOneIsChecked2) {
				$(".table-container-2").css({
					"width": "100%",
					"margin-left": "0px"
				});
				$('.comm-2').css('width', '15%');
				$('.w-40').removeAttr('style');

			} else if (atLeastOneIsChecked1) {
				$(".table-container-2").css({
					"width": "100%",
					"margin-left": "0px"
				});
				$('.comm-2').css('width', '20%');
				$('.w-40').removeAttr('style');

			} else if (atLeastOneIsChecked0) {

				$(".table-container-2").css({
					"width": "70%",
					"margin-left": "15%"
				});
				$('.comm-2').css('width', '30%');
				$('.w-40').removeAttr('style');

			} else {

				$(".table-container-2").removeAttr("style");
				// $('.comm-2').removeAttr('style');
				$('.w-40').removeAttr('style');

			}

		});

		$('#pre_school_h').on('change', function() {
			$('.14').removeAttr('style');
			$('.15').removeAttr('style');
			if ($(this).is(':checked')) {
				$('.pre').addClass('hide');
				$('.20-2').show();
				if ($('#Social').is(':checked')) {
					$('.Social-body_n').addClass('hide');
				} else {
					// $('.school_problems_p').removeClass('hide');
					$('.attention2_p').removeClass('hide');
				}
				if ($('#adaptive').is(':checked')) {
					$('.15').removeClass('hide').attr('style', 'border-bottom: 3px solid #000000');
				} else {
					$('.22').attr('style', 'border-bottom: 3px solid #000000');
				}
				$('.15').removeClass('hide');
			} else {
				$('.20-2').hide();
				$('.pre').removeClass('hide');
				if ($('#Social').is(':checked')) {
					$('.Social-body_n').addClass('hide');
				} else {
					// $('.school_problems_p').removeClass('hide');
					$('.attention2_p').addClass('hide');
				}
				if ($('#adaptive').is(':checked')) {
					$('.14').attr('style', 'border-bottom: 3px solid #000000');
				} else {
					$('.22').attr('style', 'border-bottom: 3px solid #000000');
				}
				$('.15').addClass('hide');
			}
		});

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$(document).on('change', 'input.full', function() {
			var ch_value = $(this).val();
			var id = $(this).attr('id');
			var prev_div = $('[re-attr="' + id + '"]');
			var curr = prev_div.closest('td');

			$('lable[la-attr="' + id + '"]').removeAttr('style').css('display', 'none');

			for (let p = 0; p < 23; p++) {
				if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu input').val())) {
					$('#table-container .' + p + ' .show_hide_fu .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu input').val());

				} else {
					$('#table-container .' + p + ' .show_hide_fu .total_memoryindex').text("");
				}
				if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu0 input').val())) {
					$('#table-container .' + p + ' .show_hide_fu0 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu0 input').val());
				} else {
					$('#table-container .' + p + ' .show_hide_fu0 .total_memoryindex').text("");
				}
				if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu1 input').val())) {
					$('#table-container .' + p + ' .show_hide_fu1 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu1 input').val());
				} else {
					$('#table-container .' + p + ' .show_hide_fu1 .total_memoryindex').text("");
				}
				if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu2 input').val())) {
					$('#table-container .' + p + ' .show_hide_fu2 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu2 input').val());
				} else {
					$('#table-container .' + p + ' .show_hide_fu2 .total_memoryindex').text("");
				}
				if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu3 input').val())) {
					$('#table-container .' + p + ' .show_hide_fu3 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu3 input').val());
				} else {
					$('#table-container .' + p + ' .show_hide_fu3 .total_memoryindex').text("");
				}
			}

			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			if ($.isNumeric(ch_value)) {
				var star_text = '';

				if ($('.adaptive-body').find($('#' + id)).length > 0 == true) {
					if (ch_value < 41) {
						star_text = '*';
						var cut_attr = 'At-Risk';
						var back_ground_c = 'background-color: #ffe699 !important';

						$(this).css({
							'width': '60%',
							'text-align': 'right',
							'padding': '0px',
							'margin-top': '5px'
						});
						$('lable[la-attr="' + id + '"]').css({
							'margin-top': '5px',
							'font-size': '22px'
						});
						if (ch_value < 31) {
							star_text = '**';
							cut_attr = 'Clinically Significant';
							back_ground_c = 'background-color: #e06c6c';
						}
						$('lable[la-attr="' + id + '"]').text(star_text).css('display', 'block');
						$(prev_div).text(cut_attr);
						curr.attr("style", back_ground_c);

					} else {
						cut_attr = 'Average';
						$(this).removeAttr('style');
						$('lable[la-attr="' + id + '"]').text(star_text).css('display', 'none');
						$(prev_div).text(cut_attr);
						curr.removeAttr('style');
					}

				} else {
					if (ch_value > 59) {
						star_text = '*';
						var cut_attr = 'At-Risk';
						var back_ground_c = 'background-color: #ffe699';
						$(this).css({
							'width': '60%',
							'text-align': 'right',
							'padding': '0px',
							'margin-top': '5px'
						});
						$('lable[la-attr="' + id + '"]').css({
							'margin-top': '5px',
							'font-size': '22px'
						});
						if (ch_value > 69) {
							star_text = '**';
							cut_attr = 'Clinically Significant';
							back_ground_c = 'background-color: #e06c6c';
						}
						$('lable[la-attr="' + id + '"]').text(star_text).css('display', 'block').attr('attr', cut_attr);

						// secount table
						$(prev_div).text(cut_attr);
						curr.attr("style", back_ground_c);

						// prev_div.attr('style', back_ground_c);
						// console.log(cut_attr);
					} else {
						cut_attr = 'Average';
						$(this).removeAttr('style');
						$('lable[la-attr="' + id + '"]').text(star_text).css('display', 'none').attr('attr', cut_attr);
						$(prev_div).text(cut_attr);
						curr.removeAttr('style');
					}

				}

			} else {
				$(this).css({
					'width': '40%',
					'text-align': 'right',
					'padding': '0px'
				});
				$('lable[la-attr="' + id + '"]').css({
					'margin-top': '8px',
					'padding-left': '10px',
					'font-size': '14px'
				});
				$('lable[la-attr="' + id + '"]').text('N/A').css('display', 'block');
			}

		});

		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if ($('#adaptive').is(':checked') && $('#Social').is(':checked')) {
			$('.social_adaptive_checked').removeClass('hide');

		} else {
			if ($('#Social').is(':checked')) {
				$('.social_adaptive_checked').removeClass('hide');

			} else {
				$('.social_adaptive_checked').addClass('hide');

			}
		}
		$('.22').attr('style', 'border-bottom: 3px solid #000000');

		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$('#show-rows-3 input').on('change', function() {
			var attr_val = $(this).attr('attr');

			if ($(this).is(':checked')) {
				if (attr_val == 'adaptive') {
					if ($('#pre_school_h').is(':checked')) {
						$('.15').attr('style', 'border-bottom: 3px solid #000000');
					} else {
						$('.14').attr('style', 'border-bottom: 3px solid #000000');
					}
				} else {
					$('.22').attr('style', 'border-bottom: 3px solid #000000');
				}
				$('.' + attr_val + '-body').addClass('hide');
			} else {
				$('.' + attr_val + '-body').removeClass('hide');

				$('.14').removeAttr('style');
				$('.15').removeAttr('style');
				if (attr_val == 'adaptive') {

					$('.22').attr('style', 'border-bottom: 3px solid #000000');
				} else {
					if ($('#adaptive:checked').length > 0 == true) {
						if ($('#pre_school_h').is(':checked')) {
							$('.15').attr('style', 'border-bottom: 3px solid #000000');
						} else {
							$('.14').attr('style', 'border-bottom: 3px solid #000000');
						}
					}
				}
				/////////////////

			}

			//secount table
			if ($('#adaptive').is(':checked')) {
				$('.19-2 td').removeClass('bottom');
				$('.13-2 td').addClass('bottom');
			} else {
				$('.19-2 td').addClass('bottom');
				$('.13-2 td').removeClass('bottom');
			}
			if ($('#Social').is(':checked')) {
				$('.19-2 td').addClass('bottom');
				$('.13-2 td').removeClass('bottom');
			}
			if ($('#adaptive').is(':checked') && $('#Social').is(':checked')) {
				$('.social_adaptive_checked').removeClass('hide');

			} else {
				if ($('#Social').is(':checked')) {
					$('.social_adaptive_checked').removeClass('hide');

				} else {
					$('.social_adaptive_checked').addClass('hide');

				}
			}
		});

		$('#informant_two_relationship option[value="general education teacher"]').attr("selected", "selected");;

		//black out

		var drop_val = $('.drop_style').map(function() {
			return $('option[value="' + $(this).val() + '"]').attr('ch_attr');
		});
		// console.log(drop_val);
		for (let yu = 0; yu < 5; yu++) {
			if (drop_val[yu] == "Parent") {
				$('.black_out' + yu + ' input').addClass('hide');
				$('.black_out' + yu + ' span').addClass('hide');
				$('.black_out' + yu + ' lable').addClass('hide');
				// $('.black_out' + yu).attr('style', "background: #000 !important;");
				$('.black_out' + yu).addClass('black-mask');
			} else {
				$('.black_out' + yu + ' input').removeClass('hide');
				$('.black_out' + yu + ' span').removeClass('hide');
				$('.black_out' + yu + ' lable').removeClass('hide');
				// $('.black_out' + yu).attr('style', (!$('.black_out' + yu).attr("style") ? "" : $('.black_out' + yu).attr("style").replace("background: #000 !important;", "")));
				$('.black_out' + yu).removeClass('black-mask');
			}
			if (drop_val[yu] != "Parent") {
				$('.black_out' + yu + '-2 input').addClass('hide');
				$('.black_out' + yu + '-2 span').addClass('hide');
				$('.black_out' + yu + '-2 lable').addClass('hide');
				// $('.black_out' + yu + '-2').attr('style', "background: #000 !important;");
				$('.black_out' + yu + '-2').addClass('black-mask');
			} else {
				$('.black_out' + yu + '-2 input').removeClass('hide');
				$('.black_out' + yu + '-2 span').removeClass('hide');
				$('.black_out' + yu + '-2 lable').removeClass('hide');
				// $('.black_out' + yu + '-2').attr('style', (!$('.black_out' + yu + '-2').attr("style") ? $('.black_out' + yu + '-2').attr("style") : $('.black_out' + yu + '-2').attr("style").replace("background: #000 !important;", "")));
				$('.black_out' + yu + '-2').removeClass('black-mask');

			}
		};


		$('.drop_style').on('change', function() {
			var drop_val = $('.drop_style').map(function() {
				return $('option[value="' + $(this).val() + '"]').attr('ch_attr');
			});
			for (let yu = 0; yu < 5; yu++) {
				if (drop_val[yu] == "Parent") {
					$('.black_out' + yu + ' input').addClass('hide');
					$('.black_out' + yu + ' span').addClass('hide');
					$('.black_out' + yu + ' lable').addClass('hide');
					$('.black_out' + yu).addClass('black-mask');
				} else {
					$('.black_out' + yu + ' input').removeClass('hide');
					$('.black_out' + yu + ' span').removeClass('hide');
					$('.black_out' + yu + ' lable').removeClass('hide');

					$('.black_out' + yu).removeClass('black-mask');
				}
				if (drop_val[yu] != "Parent") {
					$('.black_out' + yu + '-2 input').addClass('hide');
					$('.black_out' + yu + '-2 span').addClass('hide');
					$('.black_out' + yu + '-2 lable').addClass('hide');
					$('.black_out' + yu + '-2').addClass('black-mask');
				} else {
					$('.black_out' + yu + '-2 input').removeClass('hide');
					$('.black_out' + yu + '-2 span').removeClass('hide');
					$('.black_out' + yu + '-2 lable').removeClass('hide');

					$('.black_out' + yu + '-2').removeClass('black-mask');
				}
			}

			// if (drop_val[1] == "Parent") {
			// 	$('.black_out1 input').addClass('hide');
			// 	$('.black_out1 span').addClass('hide');
			// 	$('.black_out1').attr('style', "background-color: #000 !important;");
			// } else {
			// 	$('.black_out1 input').removeClass('hide');
			// 	$('.black_out1 span').removeClass('hide');
			// 	$('.black_out1').removeAttr('style');
			// }
		})


		for (let p = 0; p < 23; p++) {
			if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu input').val())) {
				$('#table-container .' + p + ' .show_hide_fu .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu input').val());

			} else {
				$('#table-container .' + p + ' .show_hide_fu .total_memoryindex').text("");
			}
			if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu0 input').val())) {
				$('#table-container .' + p + ' .show_hide_fu0 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu0 input').val());
			} else {
				$('#table-container .' + p + ' .show_hide_fu0 .total_memoryindex').text("");
			}
			if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu1 input').val())) {
				$('#table-container .' + p + ' .show_hide_fu1 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu1 input').val());
			} else {
				$('#table-container .' + p + ' .show_hide_fu1 .total_memoryindex').text("");
			}
			if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu2 input').val())) {
				$('#table-container .' + p + ' .show_hide_fu2 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu2 input').val());
			} else {
				$('#table-container .' + p + ' .show_hide_fu2 .total_memoryindex').text("");
			}
			if ($.isNumeric($('#table-container .' + p + ' .show_hide_fu3 input').val())) {
				$('#table-container .' + p + ' .show_hide_fu3 .total_memoryindex').text($('#table-container .' + p + ' .show_hide_fu3 input').val());
			} else {
				$('#table-container .' + p + ' .show_hide_fu3 .total_memoryindex').text("");
			}
		}

		$('.cu_lable').attr('attr', 'Average');

		$('.copy-picture-of-table').click(function() {

			$('#table-container input').hide();
			// $('.color_code').removeClass('orange-color');
			// start animation
			//$('.copied-bell-curve').hide();
			//$('.copying-bell-curve').show();
			document.body.style.cursor = 'wait';
			$('#table-container input').each(function() {
				$('.' + $(this).attr('id') + '.text-values').text($(this).val());
			});

			var five_cli = $('#3').is(':checked');
			var four_cli = $('#2').is(':checked');

			if (four_cli) {
				$('#table-container').addClass("cp_size-a");

			}

			if (five_cli) {
				$('#table-container').addClass("cp_size-a");

			}

			$('#table-container').addClass("cp_size");



			$('#table-container .text-values').show();


			$('#table-cells tr td p').attr('style', 'font-size:10px;font-weight:bold');


			// Start creating image
			html2canvas(document.querySelector('#table-container')).then(canvas => canvas.toBlob(blob => navigator.clipboard.write([new ClipboardItem({
				'image/png': blob
			})]).then(function() {

				console.log('Copied to clipboard successfully!');
				document.body.style.cursor = 'default';
				// Stop spinner
				$('.copied-table-picture').show();
				$('.color_code').addClass('orange-color');
				$('#table-container input').show();
				// Hide field values as text again
				$('#table-container .text-values').hide();
				// $('.orange-color').();


				$('#table-container').removeClass("cp_size");
				$('#table-cells tr td p').removeAttr('style', 'font-size:10px;font-weight:bold');
				if (five_cli) {
					$('#table-container').removeClass("cp_size-a");

				}
				if (four_cli) {
					$('#table-container').removeClass("cp_size-a");

				}

				setTimeout(function() {
					$('.copied-table-picture').fadeOut();
				}, 3000);
				$('#table-container table tr').removeClass('score_td');
			}, function() {
				console.error('Unable to write to clipboard. :-(');
				document.body.style.cursor = 'default';
				// Stop spinner
				//$('.copying-bell-curve').hide();

			})));
			$('#table-container table tr').removeClass('score_td');

		});

		//gaph table copy
		$('.copy-picture-of-graph').click(function() {

			document.body.style.cursor = 'wait';
			// Start creating image
			html2canvas(document.querySelector('#table-container-2')).then(canvas => canvas.toBlob(blob => navigator.clipboard.write([new ClipboardItem({
				'image/png': blob
			})]).then(function() {

				// console.log('Copied to clipboard successfully!');
				document.body.style.cursor = 'default';
				// Stop spinner
				$('.copied-table-picture').show();
				$('.color_code').addClass('orange-color');

				//  $('.copying-bell-curve').hide();
				// Fade out the animation
				setTimeout(function() {
					$('.copied-table-picture').fadeOut();
				}, 3000);
			}, function() {
				console.error('Unable to write to clipboard. :-(');
				document.body.style.cursor = 'default';
				// Stop spinner
				//$('.copying-bell-curve').hide();
			})));

		});
	</script>
</section>
<?php get_footer(); ?>