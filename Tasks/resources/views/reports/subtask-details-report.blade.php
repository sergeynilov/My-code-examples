{{-- Report Content --}}
<table style="{{ $contentTableStyle }}">

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:50%">
            ID:
        </td>
        <td  style="{{ $contentTableTdStyle }}; width:50%">
            <h1>
                {{ $task->id }}
            </h1>
        </td>
    </tr>

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Title of subtask: </small>
        </td>
        <td  style="{{ $contentTableTdStyle }}; width:50%">
            <h4>
                {{ $task->title }}
            </h4>
        </td>
    </tr>

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Of user:</small>
        </td>
        <td  style="{{ $contentTableTdStyle }}; width:50%">
            <h5>
                {{ $task['user']['name'] }}<br/>
            </h5>
        </td>
    </tr>

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Priority:</small>
        </td>
        <td  style="{{ $contentTableTdStyle }}; width:50%">
            <h5>
                {{ \App\Enums\TaskPriority::getLabel( \App\Enums\TaskPriority::from($task->priority) ) }}
            </h5>
        </td>
    </tr>

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Status:</small>
        </td>
        <td  style="{{ $contentTableTdStyle }}; width:50%">
            <h>
                {{ \App\Enums\TaskStatus::getLabel( \App\Enums\TaskStatus::from($task->status) ) }}
            </h>
        </td>
    </tr>

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:100%" colspan="2">
            <small>Description:</small>
        </td>
    </tr>

    <tr>
        <td  style="{{ $contentTableTdStyle }}; width:100%" colspan="2">
            <h5>
                {!! $task->description !!}
            </h5>
        </td>
    </tr>

    @if(!empty($task->completed_at))
        <tr>
            <td  style="{!! $contentTableTdStyle !!}; width:50%">
                <small>Completed at:</small>
            </td>
            <td  style="{{ $contentTableTdStyle }}; width:50%">
                <h5>
                    {{ \DateConv::getFormattedDateTime($task->completed_at) }}
                </h5>
            </td>
        </tr>
    @endif

    <tr>
        <td  style="{!! $contentTableTdStyle !!}; width:50%">
            <small>Created at:</small>
        </td>
        <td  style="{{ $contentTableTdStyle }}; width:50%">
            <h5>
                {{ \DateConv::getFormattedDateTime($task->created_at) }}
            </h5>
        </td>
    </tr>


    @foreach($task->getAttribute('children') as $taskChildren)
        <tr>
            <td  style="{!! $contentTableTdStyle !!}; width:60%">
                {{--                <small>{{ $userQuizRequestsAnswers['text'] }}</small>--}}
{{--                @include('reports.subtask-details-report', ['taskChildren' => $taskChildren])--}}

            </td>
        </tr>
    @endforeach

</table>
{{-- Report Content END --}}


