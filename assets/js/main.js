/**
 * ReUseHub - Frontend Interactive Behaviors
 */

document.addEventListener('DOMContentLoaded', function () {
  // Initialize Bootstrap tooltips & popovers
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // 1. Wishlist Interactive Toggle
  const wishlistButtons = document.querySelectorAll('.wishlist-btn');
  wishlistButtons.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const productId = this.getAttribute('data-product-id');
      if (!productId) return;

      const heartIcon = this.querySelector('i');
      const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

      fetch(baseUrl + 'wishlist.php?action=toggle_ajax&product_id=' + productId, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          if (data.action === 'added') {
            this.classList.add('active');
            heartIcon.classList.remove('fa-regular');
            heartIcon.classList.add('fa-solid');
            showToast('Item saved to your Wishlist!', 'success');
          } else {
            this.classList.remove('active');
            heartIcon.classList.remove('fa-solid');
            heartIcon.classList.add('fa-regular');
            showToast('Item removed from your Wishlist.', 'info');
          }
        } else if (data.status === 'unauthorized') {
          window.location.href = baseUrl + 'login.php';
        }
      })
      .catch(err => {
        console.error('Wishlist error:', err);
      });
    });
  });

  // 2. Interactive Rental Savings Calculator (Homepage & Product Page)
  const calcPurchaseInput = document.getElementById('calc-purchase-price');
  const calcDailyInput = document.getElementById('calc-daily-price');
  const calcDaysInput = document.getElementById('calc-rental-days');
  const calcDaysRange = document.getElementById('calc-days-range');

  if (calcPurchaseInput && calcDailyInput) {
    function updateSavingsCalculation() {
      const purchasePrice = parseFloat(calcPurchaseInput.value) || 0;
      const dailyPrice = parseFloat(calcDailyInput.value) || 0;
      const days = parseInt(calcDaysInput ? calcDaysInput.value : calcDaysRange.value) || 1;

      if (calcDaysInput && calcDaysRange) {
        calcDaysInput.value = days;
        calcDaysRange.value = days;
      }

      const rentalCost = dailyPrice * days;
      const savings = Math.max(0, purchasePrice - rentalCost);
      const savingsPercent = purchasePrice > 0 ? Math.round((savings / purchasePrice) * 100) : 0;

      const rentalCostElem = document.getElementById('calc-rental-total');
      const savingsElem = document.getElementById('calc-total-savings');
      const percentElem = document.getElementById('calc-savings-percent');

      if (rentalCostElem) rentalCostElem.innerText = '₹ ' + rentalCost.toLocaleString('en-IN');
      if (savingsElem) savingsElem.innerText = '₹ ' + savings.toLocaleString('en-IN');
      if (percentElem) percentElem.innerText = savingsPercent + '% Saved';
    }

    if (calcPurchaseInput) calcPurchaseInput.addEventListener('input', updateSavingsCalculation);
    if (calcDailyInput) calcDailyInput.addEventListener('input', updateSavingsCalculation);
    if (calcDaysInput) calcDaysInput.addEventListener('input', updateSavingsCalculation);
    if (calcDaysRange) calcDaysRange.addEventListener('input', updateSavingsCalculation);

    // Initial run
    updateSavingsCalculation();
  }

  // 3. Product Details Page - Live Booking Cost Calculator
  const startDateInput = document.getElementById('booking-start-date');
  const endDateInput = document.getElementById('booking-end-date');
  const dailyRateVal = parseFloat(document.getElementById('product-daily-rate')?.value || 0);
  const depositVal = parseFloat(document.getElementById('product-deposit-rate')?.value || 0);

  if (startDateInput && endDateInput) {
    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    startDateInput.min = today;
    endDateInput.min = today;

    function calculateBookingCost() {
      const start = new Date(startDateInput.value);
      const end = new Date(endDateInput.value);

      if (startDateInput.value && endDateInput.value) {
        if (end < start) {
          endDateInput.value = startDateInput.value;
        }

        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // inclusive
        const days = diffDays > 0 ? diffDays : 1;

        const rentalCharge = dailyRateVal * days;
        const serviceFee = Math.round(rentalCharge * 0.03); // 3% platform fee
        const totalEstimated = rentalCharge + depositVal + serviceFee;

        const daysDisplay = document.getElementById('calc-days-display');
        const rentalChargeDisplay = document.getElementById('calc-rental-charge');
        const depositDisplay = document.getElementById('calc-deposit-charge');
        const feeDisplay = document.getElementById('calc-service-fee');
        const totalDisplay = document.getElementById('calc-total-amount');

        if (daysDisplay) daysDisplay.innerText = days + ' ' + (days === 1 ? 'day' : 'days');
        if (rentalChargeDisplay) rentalChargeDisplay.innerText = '₹ ' + rentalCharge.toLocaleString('en-IN');
        if (depositDisplay) depositDisplay.innerText = '₹ ' + depositVal.toLocaleString('en-IN');
        if (feeDisplay) feeDisplay.innerText = '₹ ' + serviceFee.toLocaleString('en-IN');
        if (totalDisplay) totalDisplay.innerText = '₹ ' + totalEstimated.toLocaleString('en-IN');

        const hiddenDays = document.getElementById('hidden-total-days');
        if (hiddenDays) hiddenDays.value = days;
      }
    }

    startDateInput.addEventListener('change', function () {
      endDateInput.min = startDateInput.value;
      calculateBookingCost();
    });
    endDateInput.addEventListener('change', calculateBookingCost);

    // Initial calculation if dates pre-selected
    if (startDateInput.value && endDateInput.value) {
      calculateBookingCost();
    }
  }

  // 4. Product Gallery Thumbnail Switcher
  const mainGalleryImg = document.getElementById('main-gallery-img');
  const thumbImages = document.querySelectorAll('.gallery-thumb-item');
  if (mainGalleryImg && thumbImages.length > 0) {
    thumbImages.forEach(thumb => {
      thumb.addEventListener('click', function () {
        thumbImages.forEach(t => t.classList.remove('active', 'border-success'));
        this.classList.add('active', 'border-success');
        const targetSrc = this.getAttribute('data-full-img');
        if (targetSrc) {
          mainGalleryImg.src = targetSrc;
        }
      });
    });
  }

  // 5. Multi-image Upload Preview
  const imageUploadInput = document.getElementById('product-images-input');
  const previewContainer = document.getElementById('image-preview-grid');

  if (imageUploadInput && previewContainer) {
    imageUploadInput.addEventListener('change', function () {
      previewContainer.innerHTML = '';
      if (this.files) {
        Array.from(this.files).forEach((file, index) => {
          if (!file.type.match('image.*')) return;
          const reader = new FileReader();
          reader.onload = function (e) {
            const col = document.createElement('div');
            col.className = 'col-4 col-md-3 position-relative mb-2';
            col.innerHTML = `
              <div class="card shadow-sm border-0 overflow-hidden">
                <img src="${e.target.result}" class="card-img-top" style="height: 90px; object-fit: cover;">
                <span class="badge ${index === 0 ? 'bg-success' : 'bg-secondary'} position-absolute top-0 start-0 m-1" style="font-size: 0.65rem;">
                  ${index === 0 ? 'Primary' : '#' + (index + 1)}
                </span>
              </div>
            `;
            previewContainer.appendChild(col);
          };
          reader.readAsDataURL(file);
        });
      }
    });
  }
});

// Toast notification helper
function showToast(message, type = 'success') {
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    toastContainer.style.zIndex = '1090';
    document.body.appendChild(toastContainer);
  }

  const toastEl = document.createElement('div');
  toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : (type === 'danger' ? 'danger' : 'dark')} border-0 shadow`;
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.setAttribute('aria-atomic', 'true');
  toastEl.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <i class="fa-solid fa-circle-check me-2"></i> ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  toastContainer.appendChild(toastEl);
  const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
  bsToast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

