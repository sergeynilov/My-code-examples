<!DOCTYPE html>
<html lang="ua" xml:lang="ua" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8"/>
    <title>Coming user meetings report show</title>

    <style>
        @font-face {
            font-family: "DejaVu Sans";
            font-style: normal;
            font-weight: 400;
            src: url("/fonts/dejavu-sans/DejaVuSans.ttf");
            /* IE9 Compat Modes */
            src:
                local("DejaVu Sans"),
                url("/fonts/dejavu-sans/DejaVuSans.ttf") format("truetype");
        }
        body {
            font-family: {{ $bodyFontName ?? 'DejaVu Sans' }};
            font-size: {{ $bodyFontSize ?? '12px' }};
        }
    </style>

</head>
<body style="padding : 4px">

{{--Report Header --}}
<table style="{{ $contentTableStyle }}" >
    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:100%; text-align: center; margin-bottom: 20px;" colspan="2">
            <div style="margin-bottom: 10px;">
                {!! $topCenteredBlockContent !!}
            </div>
        </td>
    </tr>

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            {!! $leftTopBlockContent !!}
        </td>
        <td style="{!! $contentTableTdStyle !!}; width:50%; text-align: right;">
            {!! $rightTopBlockContent !!}
        </td>
    </tr>
</table>
{{--Report Header END --}}

{{-- Report Content --}}
<table style="{{ $contentTableStyle }}">

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            ID:
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h1>
                {{ $task->id }}
            </h1>
        </td>
    </tr>
    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Title:</small>
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h3>
                {{ $task->title }}
            </h3>
        </td>
    </tr>

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Of user:</small>
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h4>
                {{ $task['user']['name'] }}<br/>
            </h4>
        </td>
    </tr>

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Priority:</small>
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h4>
                {{ \App\Enums\TaskPriority::getLabel( \App\Enums\TaskPriority::from($task->priority) ) }}
            </h4>
        </td>
    </tr>

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Status:</small>
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h4>
                {{ \App\Enums\TaskStatus::getLabel( \App\Enums\TaskStatus::from($task->status) ) }}
            </h4>
        </td>
    </tr>

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:100%" colspan="2">
            <small>Description:</small>
        </td>
    </tr>

    <tr>
        <td style="{{ $contentTableTdStyle }}; width:100%" colspan="2">
            <h5>
                {!! $task->description !!}
            </h5>
        </td>
    </tr>

    @if(!empty($task->completed_at))
    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Completed at:</small>
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h4>
                {{ \DateConv::getFormattedDateTime($task->completed_at) }}
            </h4>
        </td>
    </tr>
    @endif

    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Created at:</small>
        </td>
        <td style="{{ $contentTableTdStyle }}; width:50%">
            <h4>
                {{ \DateConv::getFormattedDateTime($task->created_at) }}
            </h4>
        </td>
    </tr>


    @foreach($task->getAttribute('children') as $taskChildren)
        <tr>
            <td style="{!! $contentTableTdStyle !!}; width:50%" colspan="2">
                @include('reports.subtask-details-report', ['task' => $taskChildren])
            </td>
        </tr>
    @endforeach

</table>
{{-- Report Content END --}}


{{--Report Footer --}}
<table style="{{ $contentTableStyle }}">
    <tr>
        <td style="{!! $contentTableTdStyle !!}; width:50%">
            {!! $leftBottomBlockContent !!}
        </td>
        <td style="{!! $contentTableTdStyle !!}; width:50%; text-align: right;">
            {!! $rightBottomBlockContent !!}
        </td>
    </tr>
</table>
{{--Report Footer End--}}

</body>
</html>

