@php
/** @var \App\Models\EnquiryMessage $eMessage */
$authorIsAdmin = $eMessage->author->hasRole('admin');
@endphp
<li class="{{ $authorIsAdmin ? 'right' : 'left' }} clearfix">
    <span class="chat-img pull-{{ $authorIsAdmin ? 'right' : 'left' }}">
        <img class="img-circle" alt="User Avatar" src="http://placehold.it/50/{{ $authorIsAdmin ? 'FA6F57' : '55C1E7' }}/fff">
    </span>

    <div class="chat-body clearfix">
        <div class="header">
            <strong class="{{ $authorIsAdmin ? 'pull-right' : '' }} primary-font">{!! $eMessage->author->username !!}</strong>
            <small class="{{ $authorIsAdmin ? '' : 'pull-right' }} text-muted">
                <i class="fa fa-clock-o fa-fw"></i> {{ date('m/d/Y H:i', strtotime($eMessage->created_on)) }}
            </small>
        </div>
        <p>{!! nl2br(e($eMessage->message)) !!}</p>
    </div>
</li>
