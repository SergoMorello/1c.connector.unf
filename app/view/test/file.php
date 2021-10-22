@extends('lay.html')

@section('content')

<div class="container">
	@if($errors->any())
		<ul class="alert alert-danger" role="alert">
		@foreach($errors->all() as $error)
			<li>{{$error}}</li>
		@endforeach
		</ul>
	@endif

	<form method="post" action="{{route('submit')}}" enctype="multipart/form-data">
		<div class="input-group input-group-sm mb-3">
			<input type="file" name="file" />
		</div>
		<div class="input-group input-group-sm mb-3">
			<span class="input-group-text" id="inputGroup-sizing-sm">Name</span>
			<input type="text" name="name" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm" value="{{old('name',$name)}}">
		</div>
		<div class="col-12">
			<input type="submit" class="btn btn-primary" value="upload" />
		</div>
	</form>
</div>
@endsection
