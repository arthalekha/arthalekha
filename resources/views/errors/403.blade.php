@extends('errors.layout')

@section('code', '403')
@section('title', 'Forbidden')

@php
    $message = $exception->getMessage() ?: "You don't have permission to access this page.";
@endphp

@section('message', $message)
