@extends('layouts.dashboard')

@section('title', 'Pickup Chat — ' . ($pickupRequest->foodDonation->food_name ?? 'Donation'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ auth()->user()->role === 'restaurant' ? route('restaurant.history') : route('ngo.history') }}">Pickup History</a></li>
    <li class="breadcrumb-item active">Live Coordination Chat</li>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden chat-card">
                <!-- Chat Header -->
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ auth()->user()->role === 'restaurant' ? route('restaurant.history') : route('ngo.history') }}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width:34px;height:34px;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 fw-bold text-dark">{{ $pickupRequest->foodDonation->food_name ?? 'Surplus Food' }}</h6>
                                <span class="badge bg-success bg-opacity-10 text-success small">{{ ucfirst($pickupRequest->status) }}</span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-store me-1"></i> {{ $pickupRequest->foodDonation->restaurant->restaurant_name ?? 'Restaurant' }}
                                <span class="mx-1">•</span>
                                <i class="fas fa-hands-helping me-1"></i> {{ $pickupRequest->ngo->ngo_name ?? 'NGO' }}
                            </small>
                        </div>
                    </div>
                    <div>
                        @if($isClosed)
                            <span class="badge bg-secondary px-3 py-2"><i class="fas fa-lock me-1"></i> Closed</span>
                        @else
                            <span class="badge bg-success px-3 py-2"><i class="fas fa-circle text-white me-1" style="font-size: .5rem;"></i> Active Chat</span>
                        @endif
                    </div>
                </div>

                <!-- Chat Messages Body -->
                <div class="card-body p-4 chat-body" id="chatMessagesContainer" style="height: 480px; overflow-y: auto; background: #f8fafc;">
                    @forelse($messages as $msg)
                        @php
                            $isMe = ($msg->user_id === auth()->id());
                        @endphp
                        <div class="d-flex mb-3 {{ $isMe ? 'justify-content-end' : 'justify-content-start' }}" data-message-id="{{ $msg->id }}">
                            <div class="chat-bubble {{ $isMe ? 'bubble-me' : 'bubble-them' }} shadow-sm">
                                @if(!$isMe)
                                    <div class="chat-sender-name mb-1">
                                        {{ $msg->user->name ?? 'User' }}
                                        <span class="badge bg-light text-secondary border ms-1" style="font-size: .6rem;">{{ ucfirst($msg->user->role ?? '') }}</span>
                                    </div>
                                @endif
                                <div class="chat-text">{{ $msg->message }}</div>
                                <div class="chat-time text-end mt-1">
                                    <small>{{ $msg->created_at->format('h:i A') }}</small>
                                    @if($isMe)
                                        <i class="fas fa-check-double text-success ms-1" style="font-size: .65rem;"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted empty-chat-state" id="emptyChatNotice">
                            <i class="fas fa-comments fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-semibold">No messages yet</h6>
                            <p class="small mb-0">Coordinate pickup arrival, packaging status, or gate instructions directly here.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Chat Input Footer -->
                <div class="card-footer bg-white border-top p-3">
                    @if($isClosed)
                        <div class="alert alert-secondary py-2 px-3 mb-0 small text-center rounded-3">
                            <i class="fas fa-info-circle me-1"></i> This pickup has been completed or closed. Chat is preserved in read-only mode.
                        </div>
                    @else
                        <form id="chatForm" class="d-flex gap-2 align-items-center" onsubmit="sendMessage(event)">
                            @csrf
                            <input type="text" id="chatInput" name="message" class="form-control rounded-pill px-3 py-2 border-secondary border-opacity-25" placeholder="Type a message (e.g. 'Standing at the front entrance')..." required autocomplete="off">
                            <button type="submit" class="btn btn-success rounded-pill px-4 py-2 text-white shadow-sm" id="sendBtn">
                                <i class="fas fa-paper-plane me-1"></i> Send
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-bubble {
        max-width: 75%;
        padding: .75rem 1rem;
        border-radius: 1.1rem;
        position: relative;
    }
    .bubble-me {
        background: #059669;
        color: #ffffff;
        border-bottom-right-radius: .25rem;
    }
    .bubble-me .chat-time {
        color: rgba(255, 255, 255, 0.75);
        font-size: .7rem;
    }
    .bubble-them {
        background: #ffffff;
        color: #1e293b;
        border-bottom-left-radius: .25rem;
        border: 1px solid #e2e8f0;
    }
    .bubble-them .chat-time {
        color: #94a3b8;
        font-size: .7rem;
    }
    .chat-sender-name {
        font-size: .75rem;
        font-weight: 700;
        color: #059669;
    }
    .chat-text {
        font-size: .9rem;
        word-break: break-word;
    }
</style>
@endsection

@section('scripts')
<script>
    let lastMessageId = 0;
    const container = document.getElementById('chatMessagesContainer');

    // Scroll to bottom on load
    function scrollToBottom() {
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Determine highest current message ID
    document.querySelectorAll('[data-message-id]').forEach(el => {
        const id = parseInt(el.getAttribute('data-message-id'), 10);
        if (id > lastMessageId) lastMessageId = id;
    });

    scrollToBottom();

    // Send Message via AJAX
    function sendMessage(e) {
        e.preventDefault();
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;

        fetch("{{ route('chat.send', $pickupRequest->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            sendBtn.disabled = false;
            if (data.success && data.message) {
                appendMessage(data.message);
                input.value = '';
                lastMessageId = Math.max(lastMessageId, data.message.id);
            } else if (data.message) {
                alert(data.message);
            }
        })
        .catch(err => {
            sendBtn.disabled = false;
            console.error(err);
        });
    }

    // Append Message Element
    function appendMessage(msg) {
        const emptyNotice = document.getElementById('emptyChatNotice');
        if (emptyNotice) emptyNotice.remove();

        const row = document.createElement('div');
        row.className = `d-flex mb-3 ${msg.is_me ? 'justify-content-end' : 'justify-content-start'}`;
        row.setAttribute('data-message-id', msg.id);

        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${msg.is_me ? 'bubble-me' : 'bubble-them'} shadow-sm`;

        let senderHtml = '';
        if (!msg.is_me) {
            senderHtml = `<div class="chat-sender-name mb-1">${escapeHtml(msg.user_name)} <span class="badge bg-light text-secondary border ms-1" style="font-size: .6rem;">${escapeHtml(msg.user_role)}</span></div>`;
        }

        const checkmark = msg.is_me ? '<i class="fas fa-check-double text-success ms-1" style="font-size: .65rem;"></i>' : '';

        bubble.innerHTML = `
            ${senderHtml}
            <div class="chat-text">${escapeHtml(msg.message)}</div>
            <div class="chat-time text-end mt-1">
                <small>${msg.created_at}</small> ${checkmark}
            </div>
        `;

        row.appendChild(bubble);
        container.appendChild(row);
        scrollToBottom();
    }

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // Polling for real-time messages (every 3.5s)
    @if(!$isClosed)
    setInterval(() => {
        fetch(`{{ route('chat.poll', $pickupRequest->id) }}?last_id=${lastMessageId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    if (msg.id > lastMessageId) {
                        appendMessage(msg);
                        lastMessageId = msg.id;
                    }
                });
            }
        })
        .catch(err => console.log('Poll idle'));
    }, 3500);
    @endif
</script>
@endsection
