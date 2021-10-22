@extends('lay.html')
@php
$test = 32333;

$testArr = ['a','b','c'];
@endphp

@section('arr')
	@foreach($testArr as $it)
		<div>{{$it}}</div>
	@endforeach
@endsection


@section('content')

!!!!<br>
@test($test)

!!!!<br>
@section('title','test')


@yield('contentd')


@yield('arr')

<br>
test route:<a href="{{route('test2',[12,'wrffsdf','qqq=123'])}}">link</a><br>
test2 route:<a href="{{route('test3',[12,'wrffsdf'])}}">link</a>
<br>
<a href='https://edm.ru/ru/contacts/contacts_it_34#map' target='_blank'>test</a>


@endsection