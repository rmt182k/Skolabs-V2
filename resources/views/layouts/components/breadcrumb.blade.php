<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                <ol class="breadcrumb m-0" id="dynamic-breadcrumb">
                </ol>
            </div>
            <h4 class="page-title" id="page-title"></h4>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to format URL segments into user-friendly text
            function formatSegment(segment) {
                return segment.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }

            // Get page elements
            const breadcrumbContainer = document.getElementById('dynamic-breadcrumb');
            const pageTitleElement = document.getElementById('page-title');

            // Get URL path and filter empty segments
            const path = window.location.pathname.split('/').filter(p => p.length > 0);

            // Check if the current path is the dashboard root
            const isDashboardRoot = path.length === 1 && path[0].toLowerCase() === 'dashboard';

            // Handle dashboard root separately
            if (isDashboardRoot) {
                const dashboardItem = document.createElement('li');
                dashboardItem.classList.add('breadcrumb-item', 'active');
                dashboardItem.textContent = 'Dashboard';
                breadcrumbContainer.appendChild(dashboardItem);
                pageTitleElement.textContent = 'Dashboard';
                return; // Stop execution to prevent further breadcrumb generation
            }

            // Breadcrumb for 'Dashboard' root
            const homeItem = document.createElement('li');
            homeItem.classList.add('breadcrumb-item');
            const homeLink = document.createElement('a');
            homeLink.href = '/dashboard';
            homeLink.textContent = 'Dashboard';
            homeItem.appendChild(homeLink);
            breadcrumbContainer.appendChild(homeItem);

            let currentPath = '';

            // Generate breadcrumbs from URL segments
            path.forEach((segment, index) => {
                const isLastItem = index === path.length - 1;
                const li = document.createElement('li');
                li.classList.add('breadcrumb-item');

                const text = formatSegment(segment);

                // Add link for all segments except the last one
                if (!isLastItem) {
                    currentPath += '/' + segment;
                    const a = document.createElement('a');
                    a.href = currentPath;
                    a.textContent = text;
                    li.appendChild(a);
                } else {
                    li.textContent = text;
                    li.classList.add('active');
                }

                breadcrumbContainer.appendChild(li);

                // Set the page title based on the last segment
                if (isLastItem) {
                    pageTitleElement.textContent = text;
                }
            });

            // Special handling for the root URL
            if (path.length === 0) {
                pageTitleElement.textContent = 'Dashboard Overview';
                const overviewItem = document.createElement('li');
                overviewItem.classList.add('breadcrumb-item', 'active');
                overviewItem.textContent = 'Dashboard Overview';
                breadcrumbContainer.appendChild(overviewItem);
            }
        });
    </script>
@endpush
