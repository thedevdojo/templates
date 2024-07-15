@extends('voyager::master')

@section('css')
	<style type="text/css">

		#templates{
			margin-top:20px;
		}

		#templates .page-title{
			background-image: linear-gradient(120deg, #f093fb 0%, #f5576c 100%);
			color:#fff;
			width:100%;
			border-radius:3px;
			margin-bottom:15px;
			overflow:hidden;
		}
		#templates .page-title small{
			margin-left:10px;
			color:rgba(255, 255, 255, 0.85);
		}

		#templates .page-title:after {
		    content: '';
		    width: 110%;
		    background: rgba(255, 255, 255, 0.1);
		    position: absolute;
		    bottom: -24px;
		    z-index: 9;
		    display: block;
		    transform: rotate(-2deg);
		    height: 50px;
		    right: 0px;
		}

		#templates .page-title:before {
		    content: '';
		    width: 110%;
		    background: rgba(0, 0, 0, 0.04);
		    position: absolute;
		    top: -20px;
		    z-index: 9;
		    display: block;
		    transform: rotate(2deg);
		    height: 50px;
		    left: 0px;
		}

		.template_thumb{
			width:100%;
			height:auto;
		}

		.template{
			border:1px solid #f1f1f1;
			border-radius:3px;
		}

		.template_details{
			border-top: 1px solid #eaeaea;
    		padding: 15px;
		}

		.template_details:after{
			display:block;
			clear:both;
			content:'';
			width:100%;
		}

		.panel-body .template_details h4{
			margin-top:10px;
			float:left;
		}

		.template_details a i, .template_details span i{
			position:relative;
			top:2px;
			margin-bottom:0px;
		}

		.template_details a.btn{
			color:#79797f;
			border:1px solid #e1e1e1;
		}

		.template_details a.btn:hover{
			background:#2ecc71;
			border-color:#2ecc71;
			color:#fff;
		}

		.template_details span{
			cursor:default;
		}
		.template-options{
			padding: 8px 10px;
		    border: 1px solid #e1e1e1;
		    border-radius: 3px;
		    float: right;
		    width: 36px;
		    height: 36px;
		    margin-top: 5px;
		    margin-right: 10px;
		    cursor: pointer;
		    transition:all 0.3s ease;
		}
		.template-options:hover{
			background:#fcfcfc;
			border: 1px solid #ddd;
		}

		.template-options-trash{
			padding: 8px 10px;
		    border: 1px solid #e1e1e1;
		    border-radius: 3px;
		    float: right;
		    width: 36px;
		    height: 36px;
		    margin-top: 5px;
		    margin-right: 10px;
		    cursor: pointer;
		    transition:all 0.3s ease;
		}
		.template-options-trash:hover{
			background:#FA2A00;
			border: 1px solid #FA2A00;
			color:#fff;
		}

		.row>[class*=col-]{
			margin-bottom:0px;
		}

		h2{
			padding-top:10px;
		}
		.template_details h4{
			position:relative;
		}
		.template_details h4 span{
			font-size: 10px;
		    position: absolute;
		    right: 0px;
		    bottom: -12px;
		    color: #999;
		    font-weight: lighter;
		}

	</style>
@endsection

@section('content')

<div id="templates">

	<div class="container-fluid">

		<h1 class="page-title">
        	<i class="voyager-paint-bucket"></i> Templates
        	<small>Choose a template below</small>
        </h1>

        @if(count($templates) < 1)
	        <div class="alert alert-warning">
	            <strong>Wuh oh!</strong>
	            <p>It doesn't look like you have any templates available in your template folder located at <code><?= resource_path('views/templates'); ?></code></p>
	        </div>
	    @endif

        <div class="panel">
        	<div class="panel-body">

        		<div class="row">

        			@if(count($templates) < 1)
        				<div class="col-md-12">
        					<h2>No Templates Found</h2>
        					<p>That's ok, you can download a <a href="https://github.com/thedevdojo/sample-template" target="_blank">sample template here</a>, or download the <a href="https://github.com/thedevdojo/pages" target="_blank">default pages here</a>. Make sure to download the template and place it in your templates folder.</p>
        				</div>
        			@endif

	        		@foreach($templates as $template)

	        			<div class="col-md-4">
	        				<div class="template">
		        				<img class="template_thumb" src="{{ url('templates' ) }}/{{ $template->folder }}/{{ $template->folder }}.jpg">
		        				<div class="template_details">
		        					<h4>{{ $template->name }}<span>@if(isset($template->version)){{ 'version ' . $template->version }}@endif</span></h4>

		        					@if($template->active)
		        						<span class="btn btn-success pull-right"><i class="voyager-check"></i> Active</span>
		        					@else
		        						<a class="btn btn-outline pull-right" href="{{ route('voyager.template.activate', $template->folder) }}"><i class="voyager-check"></i> Activate Template</a>
		        					@endif
		        					<a href="{{ route('voyager.template.options', $template->folder) }}" class="voyager-params template-options"></a>
		        					<div class="voyager-trash template-options-trash" data-id="{{ $template->id }}"></div>


		        				</div>
		        			</div>
	        			</div>

	        		@endforeach
        		</div>

        	</div>
        </div>

    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager.generic.close') }}"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> Are you sure you want to delete this template?</h4>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('voyager.template.delete') }}" id="delete_form" method="POST">
                        {{ method_field("DELETE") }}
                        {{ csrf_field() }}
                        <input type="hidden" name="id" value="0" id="delete_id">
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                                 value="Yes, Permanantly Delete Template">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager.generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

</div>

@endsection

@section('javascript')

	<script>
		$('document').ready(function(){
			var deleteFormAction;
	        $('.template_details').on('click', '.template-options-trash', function (e) {
	            var form = $('#delete_form')[0];
	            $('#delete_id').val($(this).data('id'));
	            $('#delete_modal').modal('show');
	        });
		});
	</script>

@endsection
