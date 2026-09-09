@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-align: center;">
<img src="{{ asset('logo.png') }}" width="64" height="64" alt="{{ config('app.name') }}" class="logo" style="border-radius: 50%;">
<div>{{ config('app.name') }}</div>
</a>
</td>
</tr>
