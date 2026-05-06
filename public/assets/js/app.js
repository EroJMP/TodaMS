document.addEventListener('DOMContentLoaded', function () {
    if (window.APP_FLASH && typeof Swal !== 'undefined') {
        var flashType = window.APP_FLASH.type || 'info';
        var iconType = flashType === 'success' || flashType === 'error' || flashType === 'warning' || flashType === 'info'
            ? flashType
            : 'info';

        Swal.fire({
            icon: iconType,
            text: window.APP_FLASH.message || '',
            timer: 2000,
            showConfirmButton: false
        });
    }

    var postForms = document.querySelectorAll('form[method="POST"], form[method="post"]');
    postForms.forEach(function (form) {
        if (form.getAttribute('data-no-confirm') === '1') {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1' || typeof Swal === 'undefined') {
                return;
            }

            event.preventDefault();
            Swal.fire({
                title: 'Please confirm',
                text: 'Do you want to continue this action?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
        });
    });

    var toggles = document.querySelectorAll('.toggle-password');

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var targetId = toggle.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            var input = document.getElementById(targetId);
            if (!input) {
                return;
            }

            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            var icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            } else {
                toggle.textContent = isPassword ? '🙈' : '👁';
            }
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            toggle.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
        });
    });

    var modal = document.getElementById('proof-modal');
    var modalCancel = document.getElementById('proof-modal-cancel');
    var paymentIdInput = document.getElementById('proof-payment-id');
    var referencePreview = document.getElementById('proof-reference-preview');
    var amountPreview = document.getElementById('proof-amount-preview');
    var openButtons = document.querySelectorAll('.js-open-proof-modal');

    if (modal && modalCancel && paymentIdInput && referencePreview && amountPreview) {
        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                paymentIdInput.value = button.getAttribute('data-payment-id') || '';
                referencePreview.value = button.getAttribute('data-reference-no') || '';
                amountPreview.value = button.getAttribute('data-amount-to-pay') || '';
                modal.style.display = 'block';
            });
        });

        modalCancel.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    var cashPayForms = document.querySelectorAll('.js-cash-pay-form');
    cashPayForms.forEach(function (form) {
        var button = form.querySelector('.js-cash-pay-btn');
        var amountInput = form.querySelector('input[name="cash_amount_paid"]');
        if (!button || !amountInput) {
            return;
        }

        button.addEventListener('click', function () {
            if (typeof Swal === 'undefined') {
                var raw = window.prompt('Enter cash amount paid:');
                var amount = raw ? parseFloat(raw) : NaN;
                if (!isNaN(amount) && amount > 0) {
                    amountInput.value = amount.toFixed(2);
                    form.submit();
                }
                return;
            }

            Swal.fire({
                title: 'Cash Payment',
                text: 'Enter amount paid by the driver',
                input: 'number',
                inputAttributes: {
                    min: '0.01',
                    step: '0.01'
                },
                showCancelButton: true,
                confirmButtonText: 'Record Payment'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                var amount = parseFloat(result.value || '0');
                if (isNaN(amount) || amount <= 0) {
                    Swal.fire({
                        icon: 'error',
                        text: 'Please enter a valid amount greater than zero.'
                    });
                    return;
                }
                amountInput.value = amount.toFixed(2);
                form.submit();
            });
        });
    });

    var memberApproveModal = document.getElementById('member-approve-modal');
    var memberApproveCancel = document.getElementById('member-approve-cancel');
    var memberApproveId = document.getElementById('member-approve-id');
    var memberApproveName = document.getElementById('member-approve-modal-name');
    var memberApproveButtons = document.querySelectorAll('.js-open-member-approve-modal');

    if (memberApproveModal && memberApproveCancel && memberApproveId && memberApproveName) {
        memberApproveButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                memberApproveId.value = button.getAttribute('data-member-id') || '';
                var memberName = button.getAttribute('data-member-name') || '';
                memberApproveName.textContent = memberName ? ('Member: ' + memberName) : '';
                memberApproveModal.style.display = 'block';
            });
        });

        memberApproveCancel.addEventListener('click', function () {
            memberApproveModal.style.display = 'none';
        });

        memberApproveModal.addEventListener('click', function (event) {
            if (event.target === memberApproveModal) {
                memberApproveModal.style.display = 'none';
            }
        });
    }

    var liveFilterForms = document.querySelectorAll('.js-live-filter-form');
    liveFilterForms.forEach(function (form) {
        var timer = null;
        var submitWithDebounce = function () {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(function () {
                form.submit();
            }, 350);
        };

        var textInputs = form.querySelectorAll('input[type="text"], input[type="search"], input:not([type]), input[type="date"], input[type="datetime-local"]');
        textInputs.forEach(function (input) {
            input.addEventListener('input', function () {
                submitWithDebounce();
            });
        });

        var selects = form.querySelectorAll('select');
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                submitWithDebounce();
            });
        });
    });

    var retrieveBackupButtons = document.querySelectorAll('.js-mock-retrieve-backup');
    retrieveBackupButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var fileName = button.getAttribute('data-backup-file') || 'backup file';

            if (typeof Swal === 'undefined') {
                window.alert('Mock retrieve success: ' + fileName);
                return;
            }

            Swal.fire({
                title: 'Restore this backup?',
                text: 'This will restore the system data from "' + fileName + '". Continue?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Backup restored',
                    text: '"' + fileName + '" was restored successfully.',
                    timer: 1800,
                    showConfirmButton: false
                });
            });
        });
    });
});
