<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .result {
        padding: 20px;
    }
</style>

<div class="result">
    <h3>{{ get_phrase('Submitted assignment result') }}:</h3>
    <div>
        <h5>{{ get_phrase('Results') }}</h5>
        <p>{{ get_phrase('Student name') }}: {{ auth()->user()->name }}</p>
        <p>{{ get_phrase('Marks') }}: {{ $submitted_assignment->marks }}</p>
        <p>{{ get_phrase('Remarks') }}: {{ $submitted_assignment->remarks }}</p>
    </div>
</div>
