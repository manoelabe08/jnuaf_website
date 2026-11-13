<x-mail-layout>
  <p style="margin-top:0;font-size:18px"><b>{{ $title }}</b></p>
  <p>{{ $intro }}</p>
  <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:8px 0 18px">
    @foreach ($details as $label => $value)
      @if (filled($value))
        <tr>
          <td style="padding:7px 10px 7px 0;color:#55685B;font-size:13px;vertical-align:top;white-space:nowrap">{{ $label }}</td>
          <td style="padding:7px 0;font-size:14px;border-bottom:1px solid #DCE6DD">{{ $value }}</td>
        </tr>
      @endif
    @endforeach
  </table>
  <p style="text-align:center;margin:24px 0 4px">
    <a href="{{ $url }}" style="background:#14592A;color:#fff;text-decoration:none;font-weight:bold;padding:13px 22px;border-radius:999px;display:inline-block">{{ $action }}</a>
  </p>
</x-mail-layout>
