'use strict';

document.addEventListener('DOMContentLoaded', function(e)
{
    function SSESwitchStatusImage(container, link, direction)
    {
        let activeElement = container.querySelector('.sse-status-image.is-active');
        let futureElement;


        if (direction === 'prev')
        {
            futureElement = activeElement.previousElementSibling;

            // We're at the first element
            if (!futureElement)
            {
                futureElement = container.querySelector('.sse-status-image:last-of-type')
            }
        }
        else
        {
            futureElement = activeElement.nextElementSibling;

            if (!futureElement || !futureElement.classList.contains('sse-status-image'))
            {
                futureElement = container.querySelector('.sse-status-image:first-of-type');
            }
        }

        activeElement.classList.remove('is-active');
        futureElement.classList.add('is-active');

        link.blur();
    }

    document.querySelectorAll('.sse-status-image-handle-prev').forEach(function(prev_handle)
    {
        prev_handle.addEventListener('click', function(e)
        {
            e.preventDefault();

            let target = e.target;
            if (target.tagName === 'SPAN') target = target.parentElement;

            SSESwitchStatusImage(target.parentElement, target, 'prev');
        });
    });

    document.querySelectorAll('.sse-status-image-handle-next').forEach(function(next_handle)
    {
        next_handle.addEventListener('click', function(e)
        {
            e.preventDefault();

            let target = e.target;
            if (target.tagName === 'SPAN') target = target.parentElement;

            SSESwitchStatusImage(target.parentElement, target, 'next');
        });
    });
});
