<?php
/*
 * When performing data entry for a new subject user can only see demographics
 * until the desired form, diagnosis, in demographics is filled. Afterwards user
 * can only certain forms based on the diagnosis they entered.
 *
 */

return function($project_id) {

	$URL = $_SERVER['REQUEST_URI'];

	//check if we are on the right page
	if(preg_match('/record_home\.php\?.*&id=\w+/', $URL) == 1) {
		//get necesary information
		$patient_id = $_GET["id"];
        $project_json = json_decode('{
                           "control_field":{
                              "arm_name":"baseline_arm_1",
                              "field_name":"patient_type"
                           },
                           "instruments_to_show":[
                              {
                             "control_field_value":"1",
                             "instrument_names":[
                                "sdh_details",
                                "radiology_sdh",
                                "surgical_data",
                                "moca",
                                "gose",
                                "telephone_interview_of_cognitive_status"
                             ]
                              },
                              {
                             "control_field_value":"2",
                             "instrument_names":[
                                "sah_details",
                                "radiology_sah",
                                "delayed_neurologic_deterioration",
                                "ventriculostomysurgical_data",
                                "moca",
                                "gose",
                                "telephone_interview_of_cognitive_status"
                             ]
                              },
                              {
                             "control_field_value":"3",
                             "instrument_names":[
                                "sdh_details",
                                "sah_details"
                             ]
                              }
                           ]
                        }'
                        , true);

		$arm_name = $project_json['control_field']['arm_name'];
		$field_name = $project_json['control_field']['field_name'];
		$patient_data = REDCap::getData($project_id, 'json', $patient_id, $field_name, $arm_name, null, false, false, null, null, null);
		$instrument_names = json_encode(REDCap::getInstrumentNames());
	}else {
		//abort the hook
		echo "<script> console.log('aborting frsl record home page') </script>";
		return;
	}
?>

	<script>
		// frsl_record_home_page hook

		var json = <?php echo json_encode($project_json) ?>;
		var instrumentNames = <?php echo $instrument_names ?>;
		var patient_data = <?php echo $patient_data ?>;
		var control_field_name = "<?php echo $field_name ?>";
		var control_field_value;

		//set patient type if it exists
		if(ControlFieldValueIsSet(patient_data)){
			control_field_value = patient_data[0][control_field_name];
		} else {
			control_field_value = false;
		}

		//checks to see if a patient type has been selected for the current patient
		function ControlFieldValueIsSet(data) {
			if (data.length == 1 && data[0].hasOwnProperty(control_field_name)) {
				return true;
			}
			return false;
		}

		//resets the color of the rows after elements have been hidden
		function recolorRows() {
			var rows = $('.labelform').parent();
			var even = false;
			for(i = 0; i < rows.length; i++) {
				var currentRow = $(rows[i]);
				if(currentRow.is(":visible")) {
					if(even && currentRow.hasClass("odd")) {
						currentRow.attr('style', 'background-color: #eeeeee !important');
					} else if (!even && currentRow.hasClass("even")) {
						currentRow.attr('style', 'background-color: #fcfef5 !important');
					}
					even = !even;
				}
			}
		}

		//disables all rows in rows that have row headers that are a member of targets
		function disableRows(rows, targets) {

		    for (var i = 0; i < rows.length; i++) {
			 var rowText = $(rows[i].cells[0]).text();
			 if (targets.indexOf(rowText) !== -1) {
				   hideRow(rows[i]);
			 }
		    }
		}

		//enables all rows in rows that have row headers that are a member of targets
		function enableRows(rows, targets) {
		    for (var i = 0; i < rows.length; i++) {
			var rowText = $(rows[i].cells[0]).text();

			if (targets.indexOf(rowText) !== -1) {
			    showRow(rows[i]);
			}
		    }
		}

		//given a row, it displayes the row on the page
		function showRow(row) {
		    $(row).show();
		}

		//given a row, it displayes the row on the page
		function hideRow(row) {
		    $(row).hide();
		}

		//given an array of instrument names, return an array of their corresponding labels in the same order
		function convertNamesToLabels(instrumentNames) {
			var conversionTable = <?php echo $instrument_names ?>;
			var output = [];

			for(var i = 0; i < instrumentNames.length; i++) {
				output.push(conversionTable[instrumentNames[i]]);
			}

			return output;
		}


		function frsl_record_home_page(json, control_field_value) {
			var rows = $('.labelform').parent();

			//disable the table layered on top table we want to modify
			$("table.dataTable.no-footer.DTFC_Cloned").hide();

			var instruments_to_show = json["instruments_to_show"];

			//disable union of all instruments in instruments to show
			for(var i = 0; i < instruments_to_show.length; i++) {
				var instrumentNames = instruments_to_show[i]["instrument_names"];
				var instrumentLabels = convertNamesToLabels(instrumentNames);
				disableRows(rows, instrumentLabels);
			}

			if(control_field_value === false) {
				recolorRows(rows);
				return;
			}

			//parse logic and show only the desired instruments
			for(var i = 0; i < instruments_to_show.length; i++) {
				var value = instruments_to_show[i]["control_field_value"];
				var instrumentNames = instruments_to_show[i]["instrument_names"];
				var instrumentLabels = convertNamesToLabels(instrumentNames);

				if(value == control_field_value) {
					enableRows(rows, instrumentLabels);
				}
			}

			recolorRows(rows);
			//need to disable everything first and then begin enabling because some instrument_names have the same field
		}

		$(document).ready(function(){
			frsl_record_home_page(json, control_field_value);
		});

		$(window).load(function(){
			$('button[title="Collapse/uncollapse table"]').hide();
		});


	</script>
	<?php
}
?>
