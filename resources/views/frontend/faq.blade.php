@extends('layouts.frontend')

@section('title', 'FAQ — Food Waste Rescue')

@section('content')
<section class="bg-success text-white py-5">
    <div class="container text-center">
        <h1 class="fw-black display-5 mb-2">Frequently Asked Questions</h1>
        <p class="lead opacity-90 mb-0">Find quick answers to common questions about safety, logistics, and registration.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container max-w-3xl">
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                        Is there any fee to use Food Waste Rescue?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        No! Food Waste Rescue is 100% free for all registered restaurants, caterers, bakeries, and non-profit organizations.
                    </div>
                </div>
            </div>

            <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                        How is food safety maintained during redistribution?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Donors must specify preparation timestamps and strict expiry periods. NGOs inspect all items prior to transport, following standard food hygiene protocols.
                    </div>
                </div>
            </div>

            <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                        Who handles the transport and pickup logistics?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Pickup is handled by the claiming NGO's volunteer fleet or partner logistics teams. Donors simply package the items for pickup.
                    </div>
                </div>
            </div>

            <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                        How can an organization register as a verified NGO?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Simply click 'Get Started' or 'Register', select NGO as your user type, and upload your official registration certificate for admin approval.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
