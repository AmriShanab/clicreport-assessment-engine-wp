<?php

/**
 * Template Name: WISC-V
 * A full-width WISC-V template.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if (! defined('ABSPATH')) {
	exit('Direct script access denied.');
}
session_start();

$_SESSION['page_type'] = 'report';
?>
<?php get_header(); ?>
<section id="content" class="full-width cr-report">
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php echo fusion_render_rich_snippets_for_pages(); // phpcs:ignore WordPress.Security.EscapeOutput 
			?>
			<?php avada_singular_featured_image(); ?>
			<div class="post-content">
				<?php the_content(); ?>
				<?php fusion_link_pages(); ?>
			</div>
			<?php if (! post_password_required($post->ID)) : ?>
				<?php if (Avada()->settings->get('comments_pages')) : ?>
					<?php comments_template(); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endwhile; ?>
	<?php
	$plugin_dir = defined('CRAE_PLUGIN_URL') ? CRAE_PLUGIN_URL  : '';

	?>
	<link rel='stylesheet' href='<?php echo $plugin_dir; ?>assets/css/clickreport-global.css?v=1.1.0'>

	<style type="text/css">
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
		.grey input,
		.grey td {
			background: #cfcfcf !important;

		}

		.grey td:first-child {
			font-weight: bold;
		}

		table tr td:nth-child(2),
		table tr td:nth-child(3),
		table tr td:nth-child(4) {
			text-align: center;
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
			backgrgound: transparent;
		}

		td:first-child {
			width: 195px;
		}

		td:last-child {
			width: 190px;
		}

		.table-container td {
			border: 1px solid #686868;
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

		td input[type="checkbox"] {
			width: 40px
		}

		td input {
			height: auto !important;
		}

		td select {
			background-color: #ffffdb;
		}

		table tr td span {
			font-family: Arial, Helvetica, sans-serif;
			background: transparent !important;
		}

		table p {
			margin: 0;
			font-family: Arial, Helvetica, sans-serif;
			background: transparent !important;
		}

		table tr td {
			font-family: Arial, Helvetica, sans-serif;
			background: transparent !important;
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

		.orange-color {
			color: #c08703 !important;
			font-weight: bold !important;
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

		.copying-bell-curve {
			display: none;
			position: absolute;
			right: 12px;
			background: lightyellow;
			color: black;
			padding: 5px 20px;
			top: -30px;
			font-weight: bold;
			border: 2px solid yellow;
		}

		.copied-bell-curve {
			display: none;
			position: absolute;
			right: 12px;
			background: #ceface;
			color: green;
			padding: 5px 20px;
			top: -30px;
			font-weight: bold;
			border: 2px solid green;
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

		.table-container {
			border: 3px solid #000000;
		}

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

		.confidence-interval.show {
			display: table-cell;
		}

		.w-100 {
			width: 100%;
		}

		.text-values {
			display: none;
		}

		.bell-curve {
			margin-top: 40px;
			position: relative;
			margin-bottom: 250px;
		}

		.bell-curve-titles {
			position: absolute;
			left: 0;
		}

		.bell-curve-container {
			margin-left: 150px;
			width: calc(100% - 150px);
			display: block;
			position: relative;
			margin-left: 24%;
			width: calc(100% - 24%);
			min-height: 200px;
		}

		.bell-curve-bg {
			/* width: 103.36%;
			position: absolute;
			top: 0;
			z-index: 0;
			margin-top: -2%; */
			width: 80%;
			position: absolute;
			top: 0;
			z-index: 0;
			margin-top: -2%;
		}

		.bar {
			/* background: #d9ead5;
			display: block;
			height: 15.5px;
			height: 1.1vw;
			max-height:  15px;
			margin-bottom: 6px;
			margin-bottom: .35vw;
			width: calc(50% - 5px); /* calc(50% - 10px); */
			/*margin-left: 0;
			z-index: 100;
			position:  relative; */
			background: #d9ead5;
			display: block;
			height: 15.5px;
			height: 1.1vw;
			max-height: 13px;
			margin-bottom: 6px;
			margin-bottom: .35vw;
			width: calc(50% - 5px);
			/* calc(50% - 10px); */
			margin-left: -131px;
			z-index: 100;
			position: relative;
			top: 9px;
			font-size: 12px;
		}

		.bar:nth-child(3n) {
			background: #fff287;
		}

		.bar:nth-child(3n+1) {
			background: #cfcfcf;
		}

		.performance-summary-instructions-container,
		.performance-summary-container {
			border: 6px dashed #cfcfcf;
			padding: 0 10px;
			margin-top: 10px;
		}

		.narrative-summary-instructions-container,
		.narrative-summary-container {
			border: 6px dashed #cfcfcf;
			padding: 0 10px;
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
	</style>
	<div class="left">
		<div class="table-container" id="table-container">
			<div class="table-header">
				<h3>Wechsler Intelligence Scale for Children, Fifth Edition (WISC-V)</h3>
				<h4>(Standard Score Mean=100; Standard Deviation=15; Subtest Score Mean=10; Standard Deviation=3) </h4>

			</div>
			<table id="table-cells">
				<thead>
					<tr class="grey">
						<td>
							<p>Composite / Subtest</p>
						</td>
						<td>
							<p>Standard / Scaled Score</p>
						</td>
						<td class="confidence-interval">
							<p>Confidence Interval</p>
						</td>
						<td>
							<p>Percentile</p>
						</td>
						<td>
							<p>Descriptor</p>
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<p>Full Scale IQ</p>
						</td>
						<td><input type="number" name="full_scale_iq" id="full_scale_iq" value="100" max="160"><span class="full_scale_iq text-values">100</span></td>
						<td class="confidence-interval"><input type="text" name="full_scale_iq_ci" id="full_scale_iq_ci" value=""><span class="full_scale_iq_ci text-values"></span></td>
						<td>
							<p class="percentile" id="full_scale_iq_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr class="grey">
						<td>
							<p>Verbal Comprehension (Gc)</p>
						</td>
						<td><input type="number" name="verbal_comprehension" id="verbal_comprehension" value="100" max="160"><span class="verbal_comprehension text-values">100</span></td>
						<td class="confidence-interval"><input type="text" name="verbal_comprehension_ci" id="verbal_comprehension_ci" value=""><span class="verbal_comprehension_ci text-values"></span></td>
						<td>
							<p class="percentile" id="verbal_comprehension_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Similarities</p>
						</td>
						<td><input type="number" name="similarities" id="similarities" value="10" max="19" min="1"><span class="similarities text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="similarities_ci" id="similarities_ci" value=""><span class="similarities_ci text-values"></span></td>
						<td>
							<p class="percentile" id="similarities_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Vocabulary</p>
						</td>
						<td><input type="number" name="vocabulary" id="vocabulary" value="10" max="19" min="1"><span class="vocabulary text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="vocabulary_ci" id="vocabulary_ci" value=""><span class="vocabulary_ci text-values"></span></td>
						<td>
							<p class="percentile" id="vocabulary_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr class="grey">
						<td>
							<p>Visual Spatial (Gv)</p>
						</td>
						<td><input type="number" name="visual_spatial" id="visual_spatial" value="100" max="160"><span class="visual_spatial text-values">100</span></td>
						<td class="confidence-interval"><input type="text" name="visual_spatial_ci" id="visual_spatial_ci" value=""><span class="visual_spatial_ci text-values"></span></td>
						<td>
							<p class="percentile" id="visual_spatial_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Block Design</p>
						</td>
						<td><input type="number" name="block_design" id="block_design" value="10" max="19" min="1"><span class="block_design text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="block_design_ci" id="block_design_ci" value=""><span class="block_design_ci text-values"></span></td>
						<td>
							<p class="percentile" id="block_design_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Visual Puzzles</p>
						</td>
						<td><input type="number" name="visual_puzzles" id="visual_puzzles" value="10" max="19" min="1"><span class="visual_puzzles text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="visual_puzzles_ci" id="visual_puzzles_ci" value=""><span class="visual_puzzles_ci text-values"></span></td>
						<td>
							<p class="percentile" id="visual_puzzles_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr class="grey">
						<td>
							<p>Fluid Reasoning (Gf)</p>
						</td>
						<td><input type="number" name="fluid_reasoning" id="fluid_reasoning" value="100" max="160"><span class="fluid_reasoning text-values">100</span></td>
						<td class="confidence-interval"><input type="text" name="fluid_reasoning_ci" id="fluid_reasoning_ci" value=""><span class="fluid_reasoning_ci text-values"></span></td>
						<td>
							<p class="percentile" id="fluid_reasoning_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Matrix Reasoning</p>
						</td>
						<td><input type="number" name="matrix_reasoning" id="matrix_reasoning" value="10" max="19" min="1"><span class="matrix_reasoning text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="matrix_reasoning_ci" id="matrix_reasoning_ci" value=""><span class="matrix_reasoning_ci text-values"></span></td>
						<td>
							<p class="percentile" id="matrix_reasoning_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Figure Weights</p>
						</td>
						<td><input type="number" name="figure_weights" id="figure_weights" value="10" max="19" min="1"><span class="figure_weights text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="figure_weights_ci" id="figure_weights_ci" value=""><span class="figure_weights_ci text-values"></span></td>
						<td>
							<p class="percentile" id="figure_weights_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr class="grey">
						<td>
							<p>Working Memory (Gsm)</p>
						</td>
						<td><input type="number" name="working_memory" id="working_memory" value="100" max="160"><span class="working_memory text-values">100</span></td>
						<td class="confidence-interval"><input type="text" name="working_memory_ci" id="working_memory_ci" value=""><span class="working_memory_ci text-values"></span></td>
						<td>
							<p class="percentile" id="working_memory_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Digit Span</p>
						</td>
						<td><input type="number" name="digit_span" id="digit_span" value="10" max="19" min="1"><span class="digit_span text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="digit_span_ci" id="digit_span_ci" value=""><span class="digit_span_ci text-values"></span></td>
						<td>
							<p class="percentile" id="digit_span_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Picture Span</p>
						</td>
						<td><input type="number" name="picture_span" id="picture_span" value="10" max="19" min="1"><span class="picture_span text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="picture_span_ci" id="picture_span_ci" value=""><span class="picture_span_ci text-values"></span></td>
						<td>
							<p class="percentile" id="picture_span_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr class="grey">
						<td>
							<p>Processing Speed (Gs)</p>
						</td>
						<td><input type="number" name="processing_speed" id="processing_speed" value="100" max="160"><span class="processing_speed text-values">100</span></td>
						<td class="confidence-interval"><input type="text" name="processing_speed_ci" id="processing_speed_ci" value=""><span class="processing_speed_ci text-values"></span></td>
						<td>
							<p class="percentile" id="processing_speed_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Coding</p>
						</td>
						<td><input type="number" name="coding" id="coding" value="10" max="19" min="1"><span class="coding text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="coding_ci" id="coding_ci" value=""><span class="coding_ci text-values"></span></td>
						<td>
							<p class="percentile" id="coding_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
					<tr>
						<td class="indent">
							<p>Symbol Search</p>
						</td>
						<td><input type="number" name="symbol_search" id="symbol_search" value="10" max="19" min="1"><span class="symbol_search text-values">10</span></td>
						<td class="confidence-interval"><input type="text" name="symbol_search_ci" id="symbol_search_ci" value=""><span class="symbol_search_ci text-values"></span></td>
						<td>
							<p class="percentile" id="symbol_search_pr">50</p>
						</td>
						<td>
							<p class="descriptor">Average</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<table class="examinee-table">
			<tr>
				<td><label for="first_name" class='orange-color'>Examinee First Name</label></td>
				<td><input type="text" name="first_name" id="first_name" autocomplete="off"></td>
				<td class="text-right indent"><input type="checkbox" name="include_confidence_intervals" id="include_confidence_intervals"></td>
				<td class="text-left"><label for="include_confidence_intervals">Check this box to include a column for confidence intervals</label></td>
			</tr>
			<tr>
				<td><label for="gender" class='orange-color'>Examinee Gender:</label></td>
				<td>
					<select name="gender" id="gender" class="w-100">
						<option value="male">Male</option>
						<option value="female">Female</option>
						<option value="neutral">Neutral</option>
					</select>
				</td>
				<td class="text-right indent"><input type="checkbox" name="examinee_language" id="examinee_language"></td>
				<td class="text-left"><label for="examinee_language">Check this box if the examinee has a known language delay or is bilingual</label></td>
			</tr>
		</table>
		<div class="action-buttons">
			<button class="btn generate-narrative">Generate Narrative</button>
			<div class="copy-buttons">
				<a href="javascript:void(0);" class="copy-btn copy-table"><img src="/images/copy-table.png"></a>
				<a href="javascript:void(0);" class="copy-btn copy-narrative"><img src="/images/copy-narrative.png"></a>
				<a href="javascript:void(0);" class="copy-btn copy-narrative-summary"><img src="/images/copy-narrative-summary.jpg"></a>
				<a href="javascript:void(0);" class="copy-btn copy-performance-summary"><img src="/images/copy-performance-summary.png"></a>
				<a href='javascript:void(0);' class='copy-btn copy-picture-of-table'><img src='/images/copy-picture-of-table.jpg'></a>
				<div class="copied-table">Table Copied!</div>
				<div class='copied-table-picture'>Copied Picture!</div>
				<!-- <a href="javascript:;" class="copy-btn copy-tablecells"><img src="/images/copy-tablecells.png"></a> -->
			</div>
			<p class="text-center"><i>Scroll down to copy bell curve</i></p>
		</div>
		<div class="canvas-container">
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
			<a href="#" class="link-canvas" download>
				<div class="canvas"></div>
			</a>
		</div>
		<div id="explain-scr"></div>
	</div>
	<div class="right">
		<div class="narrative-title">
			<h1><span class="text-white bold mvboli">ClickReport's</span> WISC-V Narrative:</h1>
		</div>
		<div class="narrative">
			<div class="narrative-instructions-container">
				<p class="text-center">Click "Generate Narrative" to instantly generate the narrative here.</p>
			</div>
			<div class="narrative-container" id="narrative-container" style="display:none;">
				<p><br><b>Overall Cognitive Processing Ability:</b> The Wechsler Intelligence Scale for Children, Fifth Edition (WISC-V) was administered for the cognitive assessment. The WISC-V is a standardized measure of cognitive development for children between the ages of six and sixteen that places a strong emphasis on child-friendly, developmentally appropriate features and tasks. The WISC-V is an individually administered assessment that reports scores as Standard Scores (SS) with a mean (average) of 100 and standard deviation of 15, with most people scoring between 85 and 115. Cognitive, or mental, processes are foundational skills used in the act of thinking, remembering, and learning. These processes, along with other abilities and traits, combine to form an individual's general intellectual ability. Tests administered in the school environment do not capture every aspect of intelligence; however, these tests are useful in predicting how intense instruction needs to be in order for the individual to master academic content. <span class="first_name capitalize">the examinee</span>'s overall performance on the school-based measure of cognitive processing abilities falls within the <span class="full_scale_iq_desc">Average</span> range (SS= <span class="full_scale_iq">100</span>; PR= <span class="full_scale_iq_pr">50</span>).</p>
				<p><br><b>Verbal Comprehension (Gc): </b>Verbal comprehension refers to an individual's knowledge base (or general fund of information) that has built up over time, beginning in infancy. It is like your own personal library or everything you know. Verbal Comprehension involves knowledge of one's culture (e.g., who is the President of the United States?) as well as verbal- or language-based knowledge that has been developed during general life experiences and formal schooling (e.g., understanding words and their meaning, understanding street signs, acquiring developmentally appropriate knowledge of current events and history). Having well developed Verbal Comprehension skills means that one understands and uses language well, has an average or better vocabulary, has good listening skills, and is able to use language well via verbal expression. For the Similarities subtest, two words were read to <span class="first_name">the examinee</span> and <span class="he_she">she</span> <span class="was_were">was</span> asked to describe how they were similar (SS= <span class="similarities">10</span>). Additionally, on the Vocabulary subtest <span class="first_name">the examinee</span> was asked to either name depicted items shown to <span class="him_her">her</span> or define words that were orally presented (SS= <span class="vocabulary">10</span>). <span class="first_name capitalize">the examinee</span>'s overall performance on the measures of Verbal Comprehension fell within the <span class="verbal_comprehension_desc">Average</span> range (SS= <span class="verbal_comprehension">100</span>; PR= <span class="verbal_comprehension_pr">50</span>), which suggests that these skills <span class="verbal_comprehension_facilitate">facilitate</span> <span class="his_her">her</span> learning and performance. <span class="examinee_language">However, since Verbal Comprehension tasks rely on verbal knowledge gained from <span class="first_name">the examinee</span>'s formal schooling and life experiences, scores in this area are also heavily influenced by <span class="his_her">her</span> overall language ability and <span class="his_her">her</span> exposure to the dominant culture. Thus, it is important to consider <span class="first_name">the examinee</span>'s cultural and linguistic background when interpreting these scores, as <span class="he_she">she</span> may have performed better on a similar assessment in <span class="his_her">her</span> native language; as children in a bilingual environment develop their vocabulary knowledge in English more slowly than their monolingual peers (even though their total combined vocabulary knowledge across languages is larger). <span class="his_her capitalize">her</span> previously documented language delay likely made it more difficult to understand tasks and articulate <span class="his_her">her</span> ideas.</span> <span class="verbal_comprehension_benefit"><span class="first_name capitalize">The examinee</span> may benefit from increased exposure to environments rich in language that would provide new and varying experiences.</span></p>
				<p><br><b>Visual Processing (Gv):</b> Visual Processing, or visual spatial processing, refers to an individual's ability to think about visual patterns (e.g., what is the shortest route from your house to school?) and visual images (e.g., what would this shape look like if I turned it upside down?). This type of ability also involves generating, perceiving, and analyzing visual patterns and visual information (e.g., which three shapes go together to make this shape?). Additional examples of this type of ability include putting puzzles together, completing a maze (such as the ones often seen on children's menus in restaurants), and interpreting a graph or chart. For the Block Design subtest, <span class="first_name">the examinee</span> was given blocks and shown a model and/or picture and asked to recreate the design shown (SS= <span class="block_design">10</span>). For the Visual Puzzles subtest, <span class="first_name">the examinee</span> was asked to view a completed puzzle and select three response options that, when combined, reconstruct the puzzle (SS= <span class="visual_puzzles">10</span>). <span class="first_name capitalize">the examinee</span>'s overall performance on the measures of Visual Processing fell within the <span class="visual_spatial_desc">Average</span> range (SS= <span class="visual_spatial">100</span>; PR= <span class="visual_spatial_pr">50</span>). <span class="his_her capitalize">her</span> performance on the Visual Processing tasks suggests that these skills <span class="visual_spatial_facilitate">facilitate</span> <span class="his_her">her</span> learning and performance. <span class="visual_spatial_benefit">It may be beneficial to review spatial concepts and support comprehension through the use of hands-on activities and manipulatives.</span></p>
				<p><br><b>Fluid Reasoning (Gf):</b> Fluid Reasoning refers to the type of thinking that an individual may use when faced with a relatively new task that cannot be performed automatically (e.g., by relying on previously learned habits or scripts). It includes such tasks as forming and recognizing concepts (e.g., how are a dog, cat, and cow alike?), identifying and perceiving relationships (e.g., sun is to morning as moon is to night), drawing inferences (e.g., after reading a story, answering the question- <i> What will John do next?</i>), and/or reorganizing or transforming information (e.g., selecting one of several pictures to complete a puzzle/pattern). Overall, this ability can be thought of as a problem-solving type of intelligence. Fluid reasoning/problem solving skills are important for reading comprehension (e.g., making inferences from text), math (e.g., figuring out how to set up a math problem by using information provided in a word problem), and writing (e.g., writing a persuasive essay). For the Matrix Reasoning subtest, <span class="first_name">the examinee</span> viewed an incomplete matrix or series and was asked to select the response option that completed the matrix or series (SS= <span class="matrix_reasoning">10</span>). For the Figure Weights subtest, under the constraint of time, <span class="first_name">the examinee</span> was shown a scale with missing weight(s) and asked to select the response option that would keep the scale balanced (SS= <span class="figure_weights">10</span>). <span class="first_name capitalize">the examinee</span>'s overall performance on the measures of Fluid Reasoning fell within the <span class="fluid_reasoning_desc">Average</span> range (SS= <span class="fluid_reasoning">100</span>; PR= <span class="fluid_reasoning_pr">50</span>). <span class="his_her capitalize">her</span> performance on the Fluid Reasoning tasks suggests that these skills <span class="fluid_reasoning_facilitate">facilitate</span> <span class="his_her">her</span> learning and performance. <span class="fluid_reasoning_benefit"><span class="first_name capitalize">The examinee</span> may benefit from paired oral instruction with demonstrations, visual examples, and models. The use of familiar concrete references when presenting new vocabulary or concepts should be utilized.</span></p>
				<p><br><b>Working Memory (Gsm):</b> Working memory is the ability to hold information in one's mind and then use it within a few seconds. The most common example of working memory is holding a phone number in one's mind long enough to dial it. Working memory is also a part of the short-term memory system and involves manipulating or transforming information and using it in some way (e.g., saying the months of the year backwards or doing mental math). Working Memory skills are important for reading, writing, and math. For the Digit Span subtest, <span class="first_name">the examinee</span> was read a sequence of numbers and asked to recall the numbers in the same order, reverse order, and ascending order (SS= <span class="digit_span">10</span>). For the Picture Span subtest, <span class="he_she">she</span> <span class="was_were">was</span> shown a stimulus page with one or more pictures of nameable objects for a specified time and then asked to select the picture(s) from options on a response page (SS= <span class="picture_span">10</span>). <span class="first_name capitalize">the examinee</span>'s overall performance on the Working Memory measures fell within the <span class="working_memory_desc">Average</span> range (SS= <span class="working_memory">100</span>; PR= <span class="working_memory_pr">50</span>). <span class="his_her capitalize">her</span> performance on the Working Memory tasks suggests that these skills <span class="working_memory_facilitate">facilitate</span> <span class="his_her">her</span> learning and performance. <span class="working_memory_benefit">In the classroom, <span class="first_name">the examinee</span> may benefit from having information summarized in multiple modalities (Think-Pair-Share) or modeling/thinking-aloud.</span></p>
				<p><br><b>Processing Speed (Gs): </b>Processing Speed involves the ability to fluently perform simple clerical-type tasks, especially when under pressure to maintain focused attention and concentration. It can also be thought of as how quickly one can think or how quickly one can take simple tests that require simple decisions (e.g., math calculation fluency). Faster processing of information can help conserve working memory resources. On the other hand, impairment in this area reduces a person's efficiency in performing all types of mental operations causing them to respond more slowly to classroom tasks. For the Coding subtest, <span class="first_name">the examinee</span> worked within a specified time limit and used a key to copy symbols that corresponded with simple geometric shapes or numbers (SS= <span class="coding">10</span>). For the Symbol Search subtest, <span class="he_she">she</span> scanned search groups and indicated whether target symbols were present (SS= <span class="symbol_search">10</span>). <span class="first_name capitalize">the examinee</span>'s overall performance on the measures of Processing Speed fell within the <span class="processing_speed_desc">Average</span> range (SS= <span class="processing_speed">100</span>; PR= <span class="processing_speed_pr">50</span>). <span class="his_her capitalize">her</span> performance on the Processing Speed tasks suggests that these skills <span class="processing_speed_facilitate">facilitate</span> <span class="his_her">her</span> learning and performance. <span class="processing_speed_benefit"><span class="first_name capitalize">The examinee</span> may benefit from the volume of writing and copying to be reduced, especially when it is not a critical component of an instructional task.</span></p>
			</div>
		</div>
		<div class="summary-title">
			<h1><span class="text-white bold mvboli">ClickReport's</span> WISC-V Summary Section:</h1>
		</div>
		<div class="summary">
			<div class="narrative-summary-instructions-container">
				<p class="text-center">Click "Generate Narrative" to instantly generate the narrative summary here.</p>
			</div>
			<div class="narrative-summary-container" id="narrative-summary-container" style="display:none;">
				<p><u><b>Narrative Summary</b></u></p>
				<p>Based on cognitive assessment results, <span class="first_name">the examinee</span>'s full scale IQ falls within the <span class="full_scale_iq_desc">Average</span> range. More specifically, <span class="he_she">she</span> obtained a score within the <span class="verbal_comprehension_desc">Average</span> range for measures of Verbal Comprehension, within the <span class="visual_spatial_desc">Average</span> range for measures of Visual Spatial abilities, within the <span class="fluid_reasoning_desc">Average</span> range on measures of Fluid Reasoning, within the <span class="working_memory_desc">Average</span> range for measures of Working Memory, and within the <span class="processing_speed_desc">Average</span> range on measures of Processing Speed.</p>
			</div>
			<div class="performance-summary-instructions-container">
				<p class="text-center">Click "Generate Narrative" to instantly generate the performance summary here.</p>
			</div>
			<div class="performance-summary-container" id="performance-summary-container" style="display:none;">
				<p><u><b>Performance Summary</b></u></p>
				<p>Performance Summary:<br><br>
					Full Scale IQ: SS=<span class="full_scale_iq">128</span>; PR= <span class="full_scale_iq_pr">97</span> &nbsp;&nbsp;<span class="full_scale_iq_desc">Average</span> <br>
					Verbal Comprehension: SS=<span class="verbal_comprehension">100</span>; PR= <span class="verbal_comprehension_pr">50</span> &nbsp;&nbsp;<span class="verbal_comprehension_desc">Average</span> <br>
					Visual Spatial: SS=<span class="visual_spatial">100</span>; PR= <span class="visual_spatial_pr">50</span> &nbsp;&nbsp;<span class="visual_spatial_desc">Average</span> <br>
					Fluid Reasoning: SS=<span class="fluid_reasoning">100</span>; PR= <span class="fluid_reasoning_pr">50</span> &nbsp;&nbsp;<span class="fluid_reasoning_desc">Average</span> <br>
					Working Memory: SS=<span class="working_memory">100</span>; PR= <span class="working_memory_pr">50</span> &nbsp;&nbsp;<span class="working_memory_desc">Average</span> <br>
					Processing Speed: SS=<span class="processing_speed">100</span>; PR= <span class="processing_speed_pr">50</span> &nbsp;&nbsp;<span class="processing_speed_desc">Average</span> </p>
			</div>
		</div>
		<div class="summary-title">
			<div class="copying-bell-curve">Copying...</div>
			<div class="copied-bell-curve">Copied!</div>
			<h1><span class="text-white bold mvboli">ClickReport's</span> WISC-V Bell Curve: <button class="copy-bell-curve" id="copy-bell-curve" style="float: right;">Copy Bell Curve</button></h1>
		</div>
		<div class="bell-curve" id="bell-curve">
			<img src="/images/bell-curve-empty.jpg" class="bell-curve-bg">
			<div class="bell-curve-container">
				<div class="bar full_scale_iq_bar">Full Scale IQ</div>
				<div class="bar processing_speed_bar">Processing Speed</div>
				<div class="bar working_memory_bar">Working Memory</div>
				<div class="bar fluid_reasoning_bar">Fluid Reasoning</div>
				<div class="bar visual_spatial_bar">Visual Spatial</div>
				<div class="bar verbal_comprehension_bar">Verbal Comprehension</div>

			</div>
		</div>
	</div>
	<script src="/js/jquery-3.5.1.min.js"></script>
	<script src="/js/html2canvas.js"></script>
	<script type="text/javascript">
		$("#copy-bell-curve").click(function() {
			console.log('Clicked copy bell curve');
			// start animation
			$('.copied-bell-curve').hide();
			$('.copying-bell-curve').show();
			document.body.style.cursor = 'wait';
			// Start creating image
			html2canvas(document.querySelector("#bell-curve")).then(canvas => canvas.toBlob(blob => navigator.clipboard.write([new ClipboardItem({
				'image/png': blob
			})]).then(function() {
				console.log("Copied to clipboard successfully!");
				document.body.style.cursor = 'default';
				// Stop spinner
				$('.copied-bell-curve').show();
				$('.copying-bell-curve').hide();
				// Fade out the animation
				setTimeout(function() {
					$('.copied-bell-curve').hide();
				}, 3000);
			}, function() {
				console.error("Unable to write to clipboard. :-(");
				document.body.style.cursor = 'default';
				// Stop spinner
				$('.copying-bell-curve').hide();
			})));
		});
	</script>
	<script type="text/javascript">
		// Percentile lookup
		var percentile_lookup = [];
		percentile_lookup['40'] = '<1';
		percentile_lookup['41'] = '<1';
		percentile_lookup['42'] = '<1';
		percentile_lookup['43'] = '<1';
		percentile_lookup['44'] = '<1';
		percentile_lookup['45'] = '<1';
		percentile_lookup['46'] = '<1';
		percentile_lookup['47'] = '<1';
		percentile_lookup['48'] = '<1';
		percentile_lookup['49'] = '<1';
		percentile_lookup['50'] = '<1';
		percentile_lookup['51'] = '<1';
		percentile_lookup['52'] = '<1';
		percentile_lookup['53'] = '<1';
		percentile_lookup['54'] = '<1';
		percentile_lookup['55'] = '<1';
		percentile_lookup['56'] = '<1';
		percentile_lookup['57'] = '<1';
		percentile_lookup['58'] = '<1';
		percentile_lookup['59'] = '<1';
		percentile_lookup['60'] = '<1';
		percentile_lookup['61'] = '<1';
		percentile_lookup['62'] = '1';
		percentile_lookup['63'] = '1';
		percentile_lookup['64'] = '1';
		percentile_lookup['65'] = '1';
		percentile_lookup['66'] = '1';
		percentile_lookup['67'] = '1';
		percentile_lookup['68'] = '2';
		percentile_lookup['69'] = '2';
		percentile_lookup['70'] = '2';
		percentile_lookup['71'] = '3';
		percentile_lookup['72'] = '3';
		percentile_lookup['73'] = '4';
		percentile_lookup['74'] = '4';
		percentile_lookup['75'] = '5';
		percentile_lookup['76'] = '5';
		percentile_lookup['77'] = '6';
		percentile_lookup['78'] = '7';
		percentile_lookup['79'] = '8';
		percentile_lookup['80'] = '9';
		percentile_lookup['81'] = '10';
		percentile_lookup['82'] = '12';
		percentile_lookup['83'] = '13';
		percentile_lookup['84'] = '14';
		percentile_lookup['85'] = '16';
		percentile_lookup['86'] = '18';
		percentile_lookup['87'] = '19';
		percentile_lookup['88'] = '21';
		percentile_lookup['89'] = '23';
		percentile_lookup['90'] = '25';
		percentile_lookup['91'] = '27';
		percentile_lookup['92'] = '30';
		percentile_lookup['93'] = '32';
		percentile_lookup['94'] = '34';
		percentile_lookup['95'] = '37';
		percentile_lookup['96'] = '40';
		percentile_lookup['97'] = '42';
		percentile_lookup['98'] = '45';
		percentile_lookup['99'] = '47';
		percentile_lookup['100'] = '50';
		percentile_lookup['101'] = '53';
		percentile_lookup['102'] = '55';
		percentile_lookup['103'] = '58';
		percentile_lookup['104'] = '61';
		percentile_lookup['105'] = '63';
		percentile_lookup['106'] = '66';
		percentile_lookup['107'] = '68';
		percentile_lookup['108'] = '70';
		percentile_lookup['109'] = '73';
		percentile_lookup['110'] = '75';
		percentile_lookup['111'] = '77';
		percentile_lookup['112'] = '79';
		percentile_lookup['113'] = '81';
		percentile_lookup['114'] = '82';
		percentile_lookup['115'] = '84';
		percentile_lookup['116'] = '86';
		percentile_lookup['117'] = '87';
		percentile_lookup['118'] = '88';
		percentile_lookup['119'] = '90';
		percentile_lookup['120'] = '91';
		percentile_lookup['121'] = '92';
		percentile_lookup['122'] = '93';
		percentile_lookup['123'] = '94';
		percentile_lookup['124'] = '95';
		percentile_lookup['125'] = '95';
		percentile_lookup['126'] = '96';
		percentile_lookup['127'] = '96';
		percentile_lookup['128'] = '97';
		percentile_lookup['129'] = '97';
		percentile_lookup['130'] = '98';
		percentile_lookup['131'] = '98';
		percentile_lookup['132'] = '98';
		percentile_lookup['133'] = '99';
		percentile_lookup['134'] = '99';
		percentile_lookup['135'] = '99';
		percentile_lookup['136'] = '99';
		percentile_lookup['137'] = '99';
		percentile_lookup['138'] = '99';
		percentile_lookup['139'] = '99.5';
		percentile_lookup['140'] = '99.6';
		percentile_lookup['141'] = '99.7';
		percentile_lookup['142'] = '99.7';
		percentile_lookup['143'] = '99.8';
		percentile_lookup['144'] = '99.8';
		percentile_lookup['145'] = '99.9';
		percentile_lookup['146'] = '99.9';
		percentile_lookup['147'] = '99.9';
		percentile_lookup['148'] = '99.9';
		percentile_lookup['149'] = '<99.9';
		percentile_lookup['150'] = '<99.9';
		percentile_lookup['151'] = '<99.9';
		percentile_lookup['152'] = '<99.9';
		percentile_lookup['153'] = '<99.9';
		percentile_lookup['154'] = '<99.9';
		percentile_lookup['155'] = '<99.9';
		percentile_lookup['156'] = '<99.9';
		percentile_lookup['157'] = '<99.9';
		percentile_lookup['158'] = '<99.9';
		percentile_lookup['159'] = '<99.9';
		percentile_lookup['160'] = '<99.9';

		function update_subtest_score_and_descriptor(clicked_el) {
			if ($(clicked_el).val() <= 2) {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Below Average');
				$(clicked_el).closest('tr').find('.percentile').text('<1');
			} else if ($(clicked_el).val() <= 3) {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Below Average');
				$(clicked_el).closest('tr').find('.percentile').text('1');
			} else if ($(clicked_el).val() <= 4) {
				$(clicked_el).closest('tr').find('.descriptor').text('Well Below Average');
				$(clicked_el).closest('tr').find('.percentile').text('2');
			} else if ($(clicked_el).val() <= 5) {
				$(clicked_el).closest('tr').find('.descriptor').text('Well Below Average');
				$(clicked_el).closest('tr').find('.percentile').text('5');
			} else if ($(clicked_el).val() <= 6) {
				$(clicked_el).closest('tr').find('.descriptor').text('Below Average');
				$(clicked_el).closest('tr').find('.percentile').text('9');
			} else if ($(clicked_el).val() <= 7) {
				$(clicked_el).closest('tr').find('.descriptor').text('Low Average');
				$(clicked_el).closest('tr').find('.percentile').text('16');
			} else if ($(clicked_el).val() <= 8) {
				$(clicked_el).closest('tr').find('.descriptor').text('Average');
				$(clicked_el).closest('tr').find('.percentile').text('25');
			} else if ($(clicked_el).val() <= 9) {
				$(clicked_el).closest('tr').find('.descriptor').text('Average');
				$(clicked_el).closest('tr').find('.percentile').text('37');
			} else if ($(clicked_el).val() <= 10) {
				$(clicked_el).closest('tr').find('.descriptor').text('Average');
				$(clicked_el).closest('tr').find('.percentile').text('50');
			} else if ($(clicked_el).val() <= 11) {
				$(clicked_el).closest('tr').find('.descriptor').text('Average');
				$(clicked_el).closest('tr').find('.percentile').text('63');
			} else if ($(clicked_el).val() <= 12) {
				$(clicked_el).closest('tr').find('.descriptor').text('Average');
				$(clicked_el).closest('tr').find('.percentile').text('75');
			} else if ($(clicked_el).val() <= 13) {
				$(clicked_el).closest('tr').find('.descriptor').text('High Average');
				$(clicked_el).closest('tr').find('.percentile').text('84');
			} else if ($(clicked_el).val() <= 14) {
				$(clicked_el).closest('tr').find('.descriptor').text('Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('91');
			} else if ($(clicked_el).val() <= 15) {
				$(clicked_el).closest('tr').find('.descriptor').text('Well Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('95');
			} else if ($(clicked_el).val() <= 16) {
				$(clicked_el).closest('tr').find('.descriptor').text('Well Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('98');
			} else if ($(clicked_el).val() <= 17) {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('99');
			} else if ($(clicked_el).val() <= 18) {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('99');
			} else if ($(clicked_el).val() <= 19) {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('>99');
			} else {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Above Average');
				$(clicked_el).closest('tr').find('.percentile').text('>99');
			}
		}

		function update_composite_descriptor(clicked_el) {
			if ($(clicked_el).val() < 70) {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Below Average');
			} else if ($(clicked_el).val() < 80) {
				$(clicked_el).closest('tr').find('.descriptor').text('Well Below Average');
			} else if ($(clicked_el).val() < 85) {
				$(clicked_el).closest('tr').find('.descriptor').text('Below Average');
			} else if ($(clicked_el).val() < 90) {
				$(clicked_el).closest('tr').find('.descriptor').text('Low Average');
			} else if ($(clicked_el).val() < 111) {
				$(clicked_el).closest('tr').find('.descriptor').text('Average');
			} else if ($(clicked_el).val() < 116) {
				$(clicked_el).closest('tr').find('.descriptor').text('High Average');
			} else if ($(clicked_el).val() < 121) {
				$(clicked_el).closest('tr').find('.descriptor').text('Above Average');
			} else if ($(clicked_el).val() < 131) {
				$(clicked_el).closest('tr').find('.descriptor').text('Well Above Average');
			} else {
				$(clicked_el).closest('tr').find('.descriptor').text('Significantly Above Average');
			}
		}

		function update_composite_percentile(clicked_el) {
			// Get the ID
			var clicked_el_id = $(clicked_el).attr('id');
			// Update the percentile
			if ($(clicked_el).val() < 40) {
				var pr = "<1";
			} else if ($(clicked_el).val() > 160) {
				var pr = "<99.9";
			} else {
				var pr = percentile_lookup[Math.floor($(clicked_el).val())];
			}
			$('#' + clicked_el_id + '_pr').text(pr);
		}

		function update_bell_curve_items(clicked_el) {
			// Get the ID
			var clicked_el_id = $(clicked_el).attr('id');
			// Calculate bell curve percentage
			var bell_curve_percentage = (($(clicked_el).val() - 40) / 120) * 100;
			// Update percentile on bell curve
			$('.' + clicked_el_id + '_bar').css('width', 'calc(' + bell_curve_percentage + '% - ' + (bell_curve_percentage / 10) + 'px)');
		}
		// Update Descriptor and Percentile
		$('#full_scale_iq').change(function(e) {
			update_composite_descriptor(this);
			update_composite_percentile(this);
			update_bell_curve_items(this);
		});
		$('#verbal_comprehension').change(function(e) {
			update_composite_descriptor(this);
			update_composite_percentile(this);
			update_bell_curve_items(this);
		});
		$('#similarities').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#vocabulary').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#visual_spatial').change(function(e) {
			update_composite_descriptor(this);
			update_composite_percentile(this);
			update_bell_curve_items(this);
		});
		$('#block_design').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#visual_puzzles').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#fluid_reasoning').change(function(e) {
			update_composite_descriptor(this);
			update_composite_percentile(this);
			update_bell_curve_items(this);
		});
		$('#matrix_reasoning').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#figure_weights').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#working_memory').change(function(e) {
			update_composite_descriptor(this);
			update_composite_percentile(this);
			update_bell_curve_items(this);
		});
		$('#digit_span').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#picture_span').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#processing_speed').change(function(e) {
			update_composite_descriptor(this);
			update_composite_percentile(this);
			update_bell_curve_items(this);
		});
		$('#coding').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#symbol_search').change(function(e) {
			update_subtest_score_and_descriptor(this);
		});
		$('#include_confidence_intervals').change(function(e) {
			if ($(this).is(':checked')) {
				$('.confidence-interval').addClass('show');
			} else {
				$('.confidence-interval').removeClass('show');
			}
		});
		// Generate Narrative
		$('.generate-narrative').click(function(e) {
			// Get the examinee's first name
			var examinee_name = $('#first_name').val();
			// Check if there is a value for the examinee's first name
			if (examinee_name == '') {
				examinee_name = 'the examinee';
			}
			// Update the examinee name
			if ($.trim(examinee_name) !== '') {
				//examinee_name = 'the examinee';
				$('.first_name').text(examinee_name);
				$('.first_name').addClass('capitalize');

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
			// Update the ranges
			$('.full_scale_iq_desc').text($('#full_scale_iq').closest('tr').find('.descriptor').text());
			$('.verbal_comprehension_desc').text($('#verbal_comprehension').closest('tr').find('.descriptor').text());
			$('.visual_spatial_desc').text($('#visual_spatial').closest('tr').find('.descriptor').text());
			$('.fluid_reasoning_desc').text($('#fluid_reasoning').closest('tr').find('.descriptor').text());
			$('.working_memory_desc').text($('#working_memory').closest('tr').find('.descriptor').text());
			$('.processing_speed_desc').text($('#processing_speed').closest('tr').find('.descriptor').text());
			// Update the scores
			$('.full_scale_iq').text($('#full_scale_iq').val());
			$('.verbal_comprehension').text($('#verbal_comprehension').val());
			$('.similarities').text($('#similarities').val());
			$('.vocabulary').text($('#vocabulary').val());
			$('.visual_spatial').text($('#visual_spatial').val());
			$('.block_design').text($('#block_design').val());
			$('.visual_puzzles').text($('#visual_puzzles').val());
			$('.fluid_reasoning').text($('#fluid_reasoning').val());
			$('.matrix_reasoning').text($('#matrix_reasoning').val());
			$('.figure_weights').text($('#figure_weights').val());
			$('.working_memory').text($('#working_memory').val());
			$('.digit_span').text($('#digit_span').val());
			$('.picture_span').text($('#picture_span').val());
			$('.processing_speed').text($('#processing_speed').val());
			$('.coding').text($('#coding').val());
			$('.symbol_search').text($('#symbol_search').val());
			// Update the percentiles
			$('.full_scale_iq_pr').text($('#full_scale_iq_pr').text());
			$('.verbal_comprehension_pr').text($('#verbal_comprehension_pr').text());
			$('.visual_spatial_pr').text($('#visual_spatial_pr').text());
			$('.fluid_reasoning_pr').text($('#fluid_reasoning_pr').text());
			$('.working_memory_pr').text($('#working_memory_pr').text());
			$('.processing_speed_pr').text($('#processing_speed_pr').text());
			// Update the facilitate/inhibit words
			if ($('#verbal_comprehension').val() < 85) {
				$('.verbal_comprehension_facilitate').text('inhibit');
				$('.verbal_comprehension_benefit').css('display', 'inline');
			} else {
				$('.verbal_comprehension_facilitate').text('facilitate');
				$('.verbal_comprehension_benefit').css('display', 'none');
			}
			if ($('#visual_spatial').val() < 85) {
				$('.visual_spatial_facilitate').text('inhibit');
				$('.visual_spatial_benefit').css('display', 'inline');
			} else {
				$('.visual_spatial_facilitate').text('facilitate');
				$('.visual_spatial_benefit').css('display', 'none');
			}
			if ($('#fluid_reasoning').val() < 85) {
				$('.fluid_reasoning_facilitate').text('inhibit');
				$('.fluid_reasoning_benefit').css('display', 'inline');
			} else {
				$('.fluid_reasoning_facilitate').text('facilitate');
				$('.fluid_reasoning_benefit').css('display', 'none');
			}
			if ($('#working_memory').val() < 85) {
				$('.working_memory_facilitate').text('inhibit');
				$('.working_memory_benefit').css('display', 'inline');
			} else {
				$('.working_memory_facilitate').text('facilitate');
				$('.working_memory_benefit').css('display', 'none');
			}
			if ($('#processing_speed').val() < 85) {
				$('.processing_speed_facilitate').text('inhibit');
				$('.processing_speed_benefit').css('display', 'inline');
			} else {
				$('.processing_speed_facilitate').text('facilitate');
				$('.processing_speed_benefit').css('display', 'none');
			}
			// Update the language delay
			if ($('#examinee_language').is(':checked')) {
				$('.examinee_language').show();
			} else {
				$('.examinee_language').hide();
			}
			// Hide the narrative instructions
			$('.narrative-instructions-container').hide();
			$('.narrative-summary-instructions-container').hide();
			$('.performance-summary-instructions-container').hide();
			// Show the narrative
			$('.narrative-container').show();
			$('.narrative-summary-container').show();
			$('.performance-summary-container').show();
		});
		$('.copy-narrative').click(function(e) {
			$(".capitalize").map(function() {
				let cap = $(this).text();
				var capitalizedText = cap.charAt(0).toUpperCase() + cap.slice(1);

				$(this).text(capitalizedText);
			});

			var urlField = document.getElementById('narrative-container');
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField);
			window.getSelection().addRange(range);
			document.execCommand('copy');
		});

		$('.copy-narrative-summary').click(function(e) {
			var urlField = document.getElementById('narrative-summary-container');
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField);
			window.getSelection().addRange(range);
			document.execCommand('copy');
		});
		$('.copy-performance-summary').click(function(e) {
			var urlField = document.getElementById('performance-summary-container');
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField);
			window.getSelection().addRange(range);
			document.execCommand('copy');
		});


		function getNewHTML() {
			return $("#table-container");
		}


		$('.copy-table').click(function() {
			// Hide input fields
			$('#table-container input').hide();
			// Update field values
			$('#table-container input').each(function() {
				$('.' + $(this).attr('id') + '.text-values').text($(this).val());
				console.log($(this).val());
			});
			// Show field values as text
			$('#table-container .text-values').show();

			var newest_html = getNewHTML()[0];
			console.log(newest_html);

			var urlField = document.getElementById('table-container');
			window.getSelection().removeAllRanges();
			var range = document.createRange();
			range.selectNode(urlField);
			window.getSelection().addRange(range);
			document.execCommand('copy');

			// Show input fields again
			$('#table-container input').show();
			// Hide field values as text again
			$('#table-container .text-values').hide();

			window.getSelection().removeAllRanges();

			// Show success message
			$('.copied-table').show();
			// Hide success message after 3 seconds
			setTimeout(function() {
				$('.copied-table').fadeOut();
			}, 3000);
		});
		$('.copy-picture-of-table').click(function() {
			// // Hide input fields
			// $('#table-container input').hide();
			// // Update field values
			// $('#table-container input').each(function() {
			// 	$('.' + $(this).attr('id') + '.text-values').text($(this).val());
			// 	console.log($(this).val());
			// });
			// // Show field values as text
			// $('#table-container .text-values').show();

			// var newest_html = getNewHTML()[0];
			// console.log(newest_html);

			// var urlField = document.getElementById('table-container');
			// window.getSelection().removeAllRanges();
			// var range = document.createRange();
			// range.selectNode(urlField);
			// window.getSelection().addRange(range);
			// document.execCommand('copy');



			// // Show input fields again
			// $('#table-container input').show();
			// // Hide field values as text again
			// $('#table-container .text-values').hide();

			// window.getSelection().removeAllRanges();

			// // Show success message
			// $('.copied-table').show();
			// // Hide success message after 3 seconds
			// setTimeout(function() {
			// 	$('.copied-table').fadeOut();
			// }, 3000);

			console.log('Clicked copy bell curve');
			$('#table-container input').hide();
			$('.color_code').removeClass('orange-color');
			// start animation
			//$('.copied-bell-curve').hide();
			//$('.copying-bell-curve').show();
			document.body.style.cursor = 'wait';
			$('#table-container input').each(function() {
				$('.' + $(this).attr('id') + '.text-values').text($(this).val());
				console.log($(this).val());
			});
			$('#table-container .text-values').show();
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