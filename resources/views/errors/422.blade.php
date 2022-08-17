@extends('errors::minimal')

@section('title', __('Хрень'))
@section('code', '422')
@section('message', $exception->getMessage())
