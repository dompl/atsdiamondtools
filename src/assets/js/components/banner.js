// --- Data ---
const categories = [
	{ title: 'Drilling', description: 'High-performance core drills and bits' },
	{ title: 'Grinding', description: 'Durable grinding tools for stonework' },
	{ title: 'Polishing', description: 'Professional polishing pads for shine' },
	{ title: 'Profiling', description: 'Edge shaping and profiling solutions' },
	{ title: 'Concrete Tools', description: 'Reliable tools for concrete cutting' },
	{ title: 'Fitting Kits', description: 'Essential accessories for tool setup' },
	{ title: 'Distar Products', description: 'Premium range from Distar brand' },
	{ title: 'Power Tools', description: 'Electric tools built for professionals' },
	{ title: 'Safety Gear', description: 'Protective equipment for safe operations' },
];

const slides = [
	{
		id: 1,
		image: 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?q=80&w=2070&auto=format&fit=crop',
		prefix: 'Professional Grade',
		title: 'Power Tools Collection',
		description: 'Engineered for durability and precision. Experience the next level of craftsmanship with our premium electric cutters.',
		buttonText: 'Explore Range',
	},
	{
		id: 2,
		image: 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?q=80&w=2070&auto=format&fit=crop',
		prefix: 'High Efficiency',
		title: 'Concrete Solutions',
		description: 'Premium diamond blades designed for reinforced concrete and heavy-duty industrial applications.',
		buttonText: 'View Products',
	},
	{
		id: 3,
		image: 'https://images.unsplash.com/photo-1531685250784-756f9f674884?q=80&w=1974&auto=format&fit=crop',
		prefix: 'Safety First',
		title: 'Protective Gear',
		description: 'Comfortable, certified protection for site safety. Ensure your team is safe without compromising on mobility.',
		buttonText: 'Shop Safety',
	},
];

// --- Initialization ---
const categoryContainer = document.getElementById('category-items');
const slidesContainer = document.getElementById('carousel-slides');
const dotsContainer = document.getElementById('carousel-dots');
const categoryBtn = document.getElementById('category-btn');
const categoryList = document.getElementById('category-list');
const categoryChevron = document.getElementById('category-chevron');

let currentSlide = 0;
let slideInterval;

// Render Categories
categories.forEach((item) => {
	const div = document.createElement('div');
	div.className = 'group px-6 py-3 hover:bg-white/10 cursor-pointer transition-colors duration-200 border-l-4 border-transparent hover:border-[#fbbf24]';
	div.innerHTML = `
          <h3 class="text-[13px] font-bold uppercase tracking-wider text-white mb-0.5 group-hover:text-[#fbbf24] transition-colors">
            ${item.title}
          </h3>
          <p class="text-[11px] text-gray-300 font-light leading-tight opacity-80 group-hover:opacity-100">
            ${item.description}
          </p>
        `;
	categoryContainer.appendChild(div);
});

// Render Slides & Dots
slides.forEach((slide, index) => {
	// Slide
	const slideDiv = document.createElement('div');
	slideDiv.className = `absolute inset-0 transition-opacity duration-1000 ease-in-out slide-item ${index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'}`;
	slideDiv.innerHTML = `
            <div class="absolute inset-0">
              <img src="${slide.image}" alt="${slide.title}" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
            </div>
            <div class="absolute inset-0 flex flex-col justify-center p-8 lg:p-16 max-w-2xl">
              <span class="inline-block py-1 px-3 mb-4 text-xs font-bold tracking-widest text-white uppercase bg-[#594652] w-fit rounded">
                ${slide.prefix}
              </span>
              <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4 leading-tight shadow-sm">
                ${slide.title}
              </h2>
              <p class="text-gray-200 text-base lg:text-lg mb-8 leading-relaxed max-w-md drop-shadow-md">
                ${slide.description}
              </p>
              <button class="px-8 py-3 bg-[#fbbf24] hover:bg-[#f59e0b] text-gray-900 font-bold uppercase tracking-wide text-sm rounded transition-transform duration-300 transform hover:-translate-y-1 hover:shadow-lg w-fit">
                ${slide.buttonText}
              </button>
            </div>
        `;
	slidesContainer.appendChild(slideDiv);

	// Dot
	const dotBtn = document.createElement('button');
	dotBtn.className = `h-2 transition-all duration-300 rounded-full ${index === 0 ? 'w-8 bg-[#fbbf24]' : 'w-2 bg-white/50 hover:bg-white'}`;
	dotBtn.onclick = () => goToSlide(index);
	dotsContainer.appendChild(dotBtn);
});

// --- Logic ---

// Menu Toggle
categoryBtn.onclick = () => {
	const isOpen = categoryList.classList.contains('grid-rows-1');
	if (isOpen) {
		categoryList.classList.remove('grid-rows-1');
		categoryList.classList.add('grid-rows-0');
		categoryChevron.classList.remove('rotate-180');
	} else {
		categoryList.classList.remove('grid-rows-0');
		categoryList.classList.add('grid-rows-1');
		categoryChevron.classList.add('rotate-180');
	}
};

// Carousel Logic
function updateCarousel() {
	const slideItems = document.querySelectorAll('.slide-item');
	const dotItems = dotsContainer.children;

	slideItems.forEach((el, i) => {
		if (i === currentSlide) {
			el.classList.remove('opacity-0', 'z-0');
			el.classList.add('opacity-100', 'z-10');
		} else {
			el.classList.remove('opacity-100', 'z-0');
			el.classList.add('opacity-0', 'z-0');
		}
	});

	Array.from(dotItems).forEach((el, i) => {
		if (i === currentSlide) {
			el.className = 'h-2 transition-all duration-300 rounded-full w-8 bg-[#fbbf24]';
		} else {
			el.className = 'h-2 transition-all duration-300 rounded-full w-2 bg-white/50 hover:bg-white';
		}
	});
}

function goToSlide(index) {
	currentSlide = index;
	updateCarousel();
	resetTimer();
}

function nextSlide() {
	currentSlide = (currentSlide + 1) % slides.length;
	updateCarousel();
	resetTimer();
}

function prevSlide() {
	currentSlide = currentSlide === 0 ? slides.length - 1 : currentSlide - 1;
	updateCarousel();
	resetTimer();
}

function resetTimer() {
	clearInterval(slideInterval);
	slideInterval = setInterval(nextSlide, 6000);
}

document.getElementById('next-btn').onclick = nextSlide;
document.getElementById('prev-btn').onclick = prevSlide;

// Start Timer
slideInterval = setInterval(nextSlide, 6000);
