@extends('admin.layouts.app')
@section('title', 'Calendar Events')

@section('content')
	
	<!-- Full Page Calender -->
	<link href="{{ URL::asset('fullcalendar/packages/core/main.css') }}" rel="stylesheet" />
	<link href="{{ URL::asset('fullcalendar/packages/daygrid/main.css') }}" rel="stylesheet" />
	<link href="{{ URL::asset('fullcalendar/packages/list/main.css') }}" rel="stylesheet" />
	
	<script src="{{ URL::asset('fullcalendar/packages/core/main.js') }}"></script>
	<script src="{{ URL::asset('fullcalendar/packages/interaction/main.js') }}"></script>
	<script src="{{ URL::asset('fullcalendar/packages/daygrid/main.js') }}"></script>
	<script src="{{ URL::asset('fullcalendar/packages/list/main.js') }}"></script>
	<script src="{{ URL::asset('fullcalendar/packages/google-calendar/main.js') }}"></script>

	<script>
	$(document).ready(function(){
		// Get the events object and parse it
		var calendarEvents = JSON.parse('<?php echo $calendarEvents->toJson() ?>');
		
		// Array to hold the events
		var events = [];

		// Itrerate the events object and push its data in another array in required format
		calendarEvents.forEach(function (calendarEvent){
			// Split the date and get y m d component from it
			eventDate = calendarEvent.event_date.split('-');

			var backgroundColor = '#3788D8';
			var borderColor = '#3788D8';
			if( calendarEvent.event_source == '2' )	// Event is created by ALI user from frontend
			{
				var backgroundColor = '#00a65a';
				var borderColor = '#00a65a';
			}

			// Push the values in events variable in required format
		    events.push({
				id: calendarEvent.id,
				title: calendarEvent.event_name,
				start: new Date(eventDate[0], ( eventDate[1] - 1 ), eventDate[2]),	// Month array start from 0 in JS that's -1
				backgroundColor: backgroundColor,
				borderColor: borderColor,
				// url: 'https://calendar.google.com/calendar/r/week/'+ eventDate[0] +'/'+ eventDate[1] +'/' + eventDate[2]
			});
		});

	  	var calendarEl = document.getElementById('calendar');

		var calendar = new FullCalendar.Calendar(calendarEl, {
		    plugins: [ 'interaction', 'dayGrid', 'list', 'googleCalendar' ],
		    events: events,
		    header: {
		      	left: 'prev,next today',
		      	center: 'title',
		      	right: 'dayGridMonth,listYear'
		    },		   
		    height: 800,
		    displayEventTime: false, // don't show the time column in list view

		    // THIS KEY WON'T WORK IN PRODUCTION!!!
		    // To make your own Google API key, follow the directions here:
		    // http://fullcalendar.io/docs/google_calendar/
		    googleCalendarApiKey: 'AIzaSyDcnW6WejpTOCffshGDDb4neIrXVUA1EAE',

		    // US Holidays
		    // events: 'en.usa#holiday@group.v.calendar.google.com',

		    // To show the event details
		    eventClick: function(arg) {
		      	var eventId = arg.event.id;

		      	// Show/Hide the steps
		    	$('#step_show_event_details').show();
		    	$('#step_add_event').hide();

  	    		$.ajax({
  					url: $('meta[name="route"]').attr('content') + '/admin/geteventdetails',
  					method: 'get',
  					data: {
  						eventId: eventId
  					},
					beforeSend: function() {
	    				$('#loading_spinner').show();
				    },
				    complete: function()
				    {
				    	$('#loading_spinner').hide();
				    },
  				    success: function(response){
  				    	// Auto-fill the form
  				    	$('#frm_add_event #event_id').val(eventId);

  				    	$('#frm_add_event #event_name').val(response.name);
  				    	$('#frm_add_event #event_description').val(response.description);
  				    	$('#frm_add_event #event_start_hour').val(response.start_hour);
  				    	$('#frm_add_event #event_start_minute').val(response.start_minute);

  				    	$('#step_show_event_details #lbl_event_name').html(response.name);
  				    	$('#step_show_event_details #lbl_event_date').html(response.date);
  				    	$('#step_show_event_details #lbl_created_by').html( ( response.source == '1' ) ? 'Admin': 'ALI User' );
  				    	$('#step_show_event_details #lbl_start_time').html(response.start_hour + ':' + response.start_minute);
  				    	$('#step_show_event_details #lbl_event_description').html(response.description);

  				    	// Show/Hide the steps
  				    	$('#step_show_event_details').show();
  				    	$('#step_add_event').hide();

  				    	// Show the modal
  				    	$('#modal_create_event').modal({ backdrop: 'static', keyboard: false });
  				    }
  				});

		      	// window.open(arg.event.url, 'google-calendar-event', 'width=700,height=600');

		      	arg.jsEvent.preventDefault() // don't navigate in main tab
		    },
		    loading: function(bool) {
		      	// document.getElementById('loading').style.display = bool ? 'block' : 'none';
		      	// $('#loading_spinner').show();
		    },
		    dateClick: function(arg) {
	        	// console.log('dateClick', calendar.formatIso(arg.date));

	        	var dt = new Date(arg.date);
	        	var newdate = dt.getFullYear() + "-" + (dt.getMonth() + 1) + "-" + dt.getDate();

	        	// Reset the form
	        	$('#frm_add_event')[0].reset();
	        	$('#event_id').val('');

	        	// Set the date in the form
	        	$('#event_date').val(newdate);

	        	$('#step_show_event_details').hide();
	  			$('#step_add_event').show();

	        	// Show the modal
	        	$('#modal_create_event').modal({ backdrop: 'static', keyboard: false });
	      	},

	  	});

	  	calendar.render();

	  	// Save new event
	  	$('#btn_save_event').click(function(){
	  		if( $('#frm_add_event').valid() )
	  		{
		  		var eventDate = $('#event_date').val();
		  		eventDate = eventDate.split('-');

		  		var eventId = $('#event_id').val();

				var $this = $(this);

	    		$.ajax({
	    			url: $('meta[name="route"]').attr('content') + '/admin/saveeventdetails',
	    			method: 'post',
	    			data: {
	    				frmData: $('#frm_add_event').serialize()
	    			},
	    			beforeSend: function() {
	    				// Show the loading button
				        $this.button('loading');
				    },
	    			headers: {
				        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				    },
				    complete: function()
				    {
				    	// Change the button to previous
				    	$this.button('reset');
				    },
				    success: function(response){
				    	if( response.resCode == 0 )
				    	{
				    		// Close the modal
				    		$('#modal_create_event').modal('hide');
				    		
				    		// Refresh the form
				    		$('#frm_add_event')[0].reset();

				    		// Show the alert
				    		showNotification('Success', response.resMsg);

				    		// Show the event on calendar
				    		if( eventId == '' )		// Add event case
				    		{
		    			  		calendar.addEvent({
		    		    			id: response.calendarEvents.id,
		    		    			title: response.calendarEvents.event_name,
		    		    			start: new Date(eventDate[0], (eventDate[1] -1), eventDate[2]),
		    		    			// url: 'https://calendar.google.com/calendar/r/week/'+ eventDate[0] +'/'+ eventDate[1] +'/' + eventDate[2]
		    		    		});
				    		}
				    		else
				    		{
				    			// Refresh the page to get the updated records
				    			setTimeout(function(){
				    			   	window.location.reload();
				    			}, 2000);
				    		}
				    	}
				    	else
				    	{
				    		showNotification('Alert', response.resMsg);
				    	}
				    }
	    		});
	  		}
	  	});

	  	// Form validation
	  	$('#frm_add_event').submit(function(e){
	  	    e.preventDefault();
	  	});
	  	$('#frm_add_event').validate({
	  		rules: {
	  			event_name: { required: true },
	  			event_start_hour: { required: true },
	  			event_start_minute: { required: true },
	  			event_description: { required: true }
	  		},
	  		messages: {
	  			event_name: { required: 'Please enter event name' },
	  			event_start_hour: { required: 'Please select time' },
	  			event_start_minute: { required: 'Please select time' },
	  			event_description: { required: 'Please enter description' }
	  		}
	  	});

	  	// To edit an existing event
	  	$('#btn_edit_event').click(function(){
	  		$('#step_show_event_details').hide();
	  		$('#step_add_event').show();
	  	});
	});
	</script>
	
	<!-- To add new event -->
	<div id="modal_create_event" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Event Details</h4>
				</div>
				<div class="modal-body">
					<div id="step_show_event_details">
						<div class="form-group">
							<label>Event Name:</label>
							<label id="lbl_event_name"></label>
						</div>
						<div class="form-group">
							<label>Event Date:</label>
							<label id="lbl_event_date"></label>
						</div>
						<div class="form-group">
							<label>Created By:</label>
							<label id="lbl_created_by"></label>
						</div>
						<div class="form-group">
							<label>Event Start Time:</label>
							<label id="lbl_start_time"></label>
						</div>
						<div class="form-group">
							<label>Event Description:</label>
							<label id="lbl_event_description"></label>
						</div>
						<button type="button" class="btn btn-primary" id="btn_edit_event">Edit</button>
					</div>
					<div id="step_add_event" style="display: none;">
						<form name="frm_add_event" id="frm_add_event" autocomplete="off">
							<div class="form-group">
								<label for="event_name">Event Name:</label>
								<input type="text" class="form-control" id="event_name" name="event_name">
								<input type="hidden" id="event_date" name="event_date" value="">
								<input type="hidden" id="event_id" name="event_id" value="">
							</div>
							<div class="form-group">
								<label>Event Start Time:</label>
								<div class="row">
									<div class="col-lg-6">
										<select class="form-control" name="event_start_hour" id="event_start_hour">
											<option value="">Select Hour</option>
											@for( $time = 0; $time < 24; $time++ )
												<option value="{{ ( strlen( $time ) == 1 ) ? '0' . $time : $time }}">{{ ( strlen($time) == 1 ) ? '0' . $time : $time }}</option>
											@endfor
										</select>
									</div>
									<div class="col-lg-6">
										<select class="form-control" name="event_start_minute" id="event_start_minute">
											<option value="">Select Minute</option>
											@for( $minute = 0; $minute < 60; $minute++ )
												<option value="{{ ( strlen( $minute ) == 1 ) ? '0' . $minute : $minute }}">{{ ( strlen($minute) == 1 ) ? '0' . $minute : $minute }}</option>
											@endfor
										</select>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label for="text">Event Description:</label>
								<textarea class="form-control" name="event_description" id="event_description"></textarea>
							</div>
							<button type="submit" class="btn btn-primary" id="btn_save_event">Submit</button>
						</form>
					</div>
				</div>
				<!-- <div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				</div> -->
			</div>
		</div>
	</div>
	
	<!-- Right side column. Contains the navbar and content of the page -->
	<aside class="right-side">
	    <!-- Content Header (Page header) -->
	    <section class="content-header">
	        <h1>
	            Dashboard
	            <small>Control panel</small>
	        </h1>
	        <ol class="breadcrumb">
	            <li><a href="{{ url('admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
	            <li class="active">Calender Events</li>
	        </ol>
	    </section>

	    <!-- Main content -->
	    <section class="content">
	    	<!-- Event color legend -->
	    	<div>
	    		<span><i class="fa fa-circle" style="color: #3788D8; font-size:18px;"></i> Admin</span>
	    		<span><i class="fa fa-circle" style="color: #00A65A; font-size:18px;"></i> ALI User</span>
	    	</div>

	        <!-- Main row -->
	        <div class="row">
	        	<div class="col-lg-12">

	        		<!-- Full Calender -->
	        		<div id='calendar'></div>

		        </div>
	       	</div>
	        <!-- /.row (main row) -->
	    </section><!-- /.content -->

	</aside>
	<!-- /.right-side -->

@endsection