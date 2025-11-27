<!-- Modal toggle button -->
	<button data-modal-target="assets-modal" data-modal-toggle="assets-modal" class="ats-btn ats-btn-sm ats-btn-yellow fixed bottom-1 left-1" type="button">
		Show Assets
	</button>

<!-- Main modal -->
<div id="assets-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
	<div class="relative p-4 w-full max-w-7xl max-h-full">
		<!-- Modal content -->
		<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
			<!-- Modal header -->
			<div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
				<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
					Design Assets
				</h3>
				<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="assets-modal">
					<svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
					</svg>
					<span class="sr-only">Close modal</span>
				</button>
			</div>
			<!-- Modal body -->
			<div class="p-4 md:p-5 space-y-6 max-h-96 overflow-y-auto">
				<!-- Colors Section -->
				<div>
					<h4 class="text-lg font-bold mb-4">Color Palette</h4>
					<div class="flex flex-wrap items-center justify-start gap-6">
						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-accent-green cursor-pointer"></div>
							<span class="text-xs">accent-green</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-primary-900 cursor-pointer"></div>
							<span class="text-xs">primary-900</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-primary-700 cursor-pointer"></div>
							<span class="text-xs">primary-700</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-primary-800 cursor-pointer"></div>
							<span class="text-xs">primary-800</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-primary-600 cursor-pointer"></div>
							<span class="text-xs">primary-600</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-primary-500 cursor-pointer"></div>
							<span class="text-xs">primary-500</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-primary-300 cursor-pointer"></div>
							<span class="text-xs">primary-300</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-neutral-500 cursor-pointer"></div>
							<span class="text-xs">neutral-500</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-ats-footer cursor-pointer"></div>
							<span class="text-xs">ats-footer</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-accent-yellow cursor-pointer"></div>
							<span class="text-xs">accent-yellow</span>
						</div>

						<div class="flex flex-col items-center text-center">
							<div class="w-8 h-8 rounded-full bg-white border border-neutral-500 cursor-pointer"></div>
							<span class="text-xs">white</span>
						</div>
					</div>
				</div>

				<!-- Buttons Section -->
				<div class="space-y-6">
					<h4 class="text-lg font-bold">Button Components</h4>

					<!-- Primary Buttons -->
					<div class="space-y-4">
						<h5 class="font-semibold text-base">Primary Buttons</h5>
						<div class="grid grid-cols-5 gap-4">
							<!-- XS Size -->
							<div class="text-center">
								<h6 class="font-medium mb-2 text-sm">XS Size</h6>
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-primary-300 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-primary-300">Primary 300</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-primary-300</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-primary-500 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-primary-500">Primary 500</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-primary-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-primary-600 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-primary-600">Primary 600</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-primary-600</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-primary-700 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-primary-700">Primary 700</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-primary-700</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-primary-800 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-primary-800">Primary 800</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-primary-800</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-primary-900 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-primary-900">Primary 900</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-primary-900</span>
									</div>
								</div>
							</div>

							<!-- SM Size -->
							<div class="text-center">
								<h6 class="font-medium mb-2 text-sm">SM Size</h6>
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-primary-300 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-primary-300">Primary 300</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-primary-300</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-primary-500 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-primary-500">Primary 500</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-primary-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-primary-600 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-primary-600">Primary 600</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-primary-600</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-primary-700 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-primary-700">Primary 700</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-primary-700</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-primary-800 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-primary-800">Primary 800</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-primary-800</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-primary-900 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-primary-900">Primary 900</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-primary-900</span>
									</div>
								</div>
							</div>

							<!-- MD Size -->
							<div class="text-center">
								<h6 class="font-medium mb-2 text-sm">MD Size</h6>
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-primary-300 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-primary-300">Primary 300</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-primary-300</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-primary-500 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-primary-500">Primary 500</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-primary-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-primary-600 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-primary-600">Primary 600</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-primary-600</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-primary-700 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-primary-700">Primary 700</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-primary-700</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-primary-800 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-primary-800">Primary 800</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-primary-800</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-primary-900 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-primary-900">Primary 900</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-primary-900</span>
									</div>
								</div>
							</div>

							<!-- LG-MD Size -->
							<div class="text-center">
								<h6 class="font-medium mb-2 text-sm">LG-MD Size</h6>
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-primary-300 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-primary-300">Primary 300</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-primary-300</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-primary-500 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-primary-500">Primary 500</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-primary-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-primary-600 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-primary-600">Primary 600</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-primary-600</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-primary-700 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-primary-700">Primary 700</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-primary-700</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-primary-800 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-primary-800">Primary 800</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-primary-800</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-primary-900 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-primary-900">Primary 900</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-primary-900</span>
									</div>
								</div>
							</div>

							<!-- LG Size -->
							<div class="text-center">
								<h6 class="font-medium mb-2 text-sm">LG Size</h6>
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-primary-300 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-primary-300">Primary 300</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-primary-300</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-primary-500 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-primary-500">Primary 500</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-primary-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-primary-600 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-primary-600">Primary 600</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-primary-600</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-primary-700 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-primary-700">Primary 700</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-primary-700</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-primary-800 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-primary-800">Primary 800</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-primary-800</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-primary-900 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-primary-900">Primary 900</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-primary-900</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Neutral Buttons -->
					<div class="space-y-4">
						<h5 class="font-semibold text-base">Neutral Buttons</h5>
						<div class="grid grid-cols-5 gap-4">
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-neutral-500 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-neutral-500">Neutral 500</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-neutral-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-neutral-700 copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-neutral-700">Neutral 700</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-neutral-700</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-neutral-500 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-neutral-500">Neutral 500</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-neutral-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-neutral-700 copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-neutral-700">Neutral 700</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-neutral-700</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-neutral-500 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-neutral-500">Neutral 500</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-neutral-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-neutral-700 copyable-btn" data-class="ats-btn ats-btn-md ats-btn-neutral-700">Neutral 700</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-neutral-700</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-neutral-500 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-neutral-500">Neutral 500</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-neutral-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-neutral-700 copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-neutral-700">Neutral 700</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-neutral-700</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-neutral-500 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-neutral-500">Neutral 500</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-neutral-500</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-neutral-700 copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-neutral-700">Neutral 700</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-neutral-700</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Accent Buttons -->
					<div class="space-y-4">
						<h5 class="font-semibold text-base">Accent Buttons</h5>
						<div class="grid grid-cols-5 gap-4">
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-accent-yellow copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-accent-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-accent-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-accent-green copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-accent-green">Green</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-accent-green</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-accent-yellow copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-accent-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-accent-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-accent-green copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-accent-green">Green</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-accent-green</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-accent-yellow copyable-btn" data-class="ats-btn ats-btn-md ats-btn-accent-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-accent-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-accent-green copyable-btn" data-class="ats-btn ats-btn-md ats-btn-accent-green">Green</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-accent-green</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-accent-yellow copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-accent-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-accent-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-accent-green copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-accent-green">Green</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-accent-green</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-accent-yellow copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-accent-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-accent-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-accent-green copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-accent-green">Green</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-accent-green</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- ATS Brand Buttons -->
					<div class="space-y-4">
						<h5 class="font-semibold text-base">ATS Brand Buttons</h5>
						<div class="grid grid-cols-5 gap-4">
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-yellow copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-dark copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-gray copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-gray">Gray</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-gray</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-yellow copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-dark copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-gray copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-gray">Gray</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-gray</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-yellow copyable-btn" data-class="ats-btn ats-btn-md ats-btn-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-dark copyable-btn" data-class="ats-btn ats-btn-md ats-btn-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-gray copyable-btn" data-class="ats-btn ats-btn-md ats-btn-gray">Gray</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-gray</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-yellow copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-dark copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-gray copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-gray">Gray</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-gray</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-yellow copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-yellow</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-dark copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-gray copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-gray">Gray</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-gray</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Outline Buttons -->
					<div class="space-y-4">
						<h5 class="font-semibold text-base">Outline Buttons</h5>
						<div class="grid grid-cols-5 gap-4">
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-outline-primary copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-outline-primary">Primary</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-outline-primary</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-outline-dark copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-outline-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-outline-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-xs ats-btn-outline-yellow copyable-btn" data-class="ats-btn ats-btn-xs ats-btn-outline-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-xs ats-btn-outline-yellow</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-outline-primary copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-outline-primary">Primary</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-outline-primary</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-outline-dark copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-outline-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-outline-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-sm ats-btn-outline-yellow copyable-btn" data-class="ats-btn ats-btn-sm ats-btn-outline-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-sm ats-btn-outline-yellow</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-outline-primary copyable-btn" data-class="ats-btn ats-btn-md ats-btn-outline-primary">Primary</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-outline-primary</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-outline-dark copyable-btn" data-class="ats-btn ats-btn-md ats-btn-outline-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-outline-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-md ats-btn-outline-yellow copyable-btn" data-class="ats-btn ats-btn-md ats-btn-outline-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-md ats-btn-outline-yellow</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-outline-primary copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-outline-primary">Primary</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-outline-primary</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-outline-dark copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-outline-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-outline-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg-md ats-btn-outline-yellow copyable-btn" data-class="ats-btn ats-btn-lg-md ats-btn-outline-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-lg-md ats-btn-outline-yellow</span>
									</div>
								</div>
							</div>
							<div class="text-center">
								<div class="space-y-2">
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-outline-primary copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-outline-primary">Primary</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-outline-primary</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-outline-dark copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-outline-dark">Dark</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-outline-dark</span>
									</div>
									<div class="flex flex-col items-center">
										<button class="ats-btn ats-btn-lg ats-btn-outline-yellow copyable-btn" data-class="ats-btn ats-btn-lg ats-btn-outline-yellow">Yellow</button>
										<span class="text-xs mt-1">ats-btn-lg ats-btn-outline-yellow</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Find all color circles
	const colorCircles = document.querySelectorAll('.w-8.h-8.rounded-full');

	function fallbackCopyTextToClipboard(text) {
		const textArea = document.createElement("textarea");
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();
		try {
			document.execCommand('copy');
			return true;
		} catch (err) {
			console.error('Fallback: Oops, unable to copy', err);
			return false;
		}
		document.body.removeChild(textArea);
	}

	function copyToClipboard(text) {
		if (!navigator.clipboard) {
			return fallbackCopyTextToClipboard(text);
		}
		return navigator.clipboard.writeText(text).then(function() {
			return true;
		}).catch(function(err) {
			console.error('Async: Could not copy text: ', err);
			return fallbackCopyTextToClipboard(text);
		});
	}

	// Color circles functionality
	colorCircles.forEach(function(circle) {
		circle.addEventListener('click', function(event) {
			event.preventDefault();

			// Find the corresponding text span
			const colorName = this.parentElement.querySelector('.text-xs').textContent;

			// Copy to clipboard
			Promise.resolve(copyToClipboard(colorName)).then(function(success) {
				if (success !== false) {
					// Show feedback
					const originalText = circle.parentElement.querySelector('.text-xs');
					const originalContent = originalText.textContent;
					originalText.textContent = 'Copied!';
					originalText.style.color = '#22c55e';

					setTimeout(function() {
						originalText.textContent = originalContent;
						originalText.style.color = '';
					}, 1000);
				}
			});
		});
	});

	// Button copy functionality
	const copyableButtons = document.querySelectorAll('.copyable-btn');

	copyableButtons.forEach(function(button) {
		button.addEventListener('click', function(event) {
			event.preventDefault();

			const classNames = this.getAttribute('data-class');

			// Copy to clipboard
			Promise.resolve(copyToClipboard(classNames)).then(function(success) {
				if (success !== false) {
					// Show feedback
					const textSpan = button.parentElement.querySelector('.text-xs');
					const originalContent = textSpan.textContent;
					textSpan.textContent = 'Copied!';
					textSpan.style.color = '#22c55e';

					setTimeout(function() {
						textSpan.textContent = originalContent;
						textSpan.style.color = '';
					}, 1000);
				}
			});
		});
	});
});
</script>