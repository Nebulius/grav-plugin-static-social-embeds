'use strict';

document.addEventListener('DOMContentLoaded', function (e)
{
    function getParentWithTagName(element, tagName)
    {
        tagName = tagName.toUpperCase();

        while (element !== null && element.tagName !== tagName)
            element = element.parentElement;

        return element;
    }

    function getParentWithClasses(element, classes)
    {
        function containsAny(list, items)
        {
            for (let i = 0; i < items.length; i++)
            {
                if (list.contains(items[i])) return true;
            }

            return false;
        }

        while (element !== null && !containsAny(element.classList, classes))
            element = element.parentElement;

        return element;
    }

    function switchStatusImage(container, link, direction)
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

    function handleVideo(container)
    {
        let image = container.querySelector('img');
        let video = container.querySelector('video');
        let badge = container.querySelector('.sse-status-image-badge');
        let play_handle = container.querySelector('.sse-status-image-handle-play');

        if (!container.dataset.videoActive || container.dataset.videoActive === 'false')
        {
            container.dataset.videoActive = 'true';

            image.classList.add('is-hidden');
            badge.classList.add('is-hidden');
            play_handle.classList.add('is-hidden');

            video.classList.add('is-active');

            // Hack to re-display controls on the video on second view
            // (else they are hidden even with the attribute set).
            if (container.classList.contains('sse-status-image-video'))
            {
                video.controls = false;
                setTimeout(function() { video.controls = true; }, 100);
            }

            video.play();

            video.addEventListener('ended', function(e)
            {
                video.classList.remove('is-active');
                image.classList.remove('is-hidden');
                badge.classList.remove('is-hidden');
                play_handle.classList.remove('is-hidden');

                container.dataset.videoActive = 'false';
            });
        }
        else if (container.classList.contains('sse-status-image-animated-gif'))
        {
            video.pause();
            video.currentTime = 0;

            video.classList.remove('is-active');
            image.classList.remove('is-hidden');
            badge.classList.remove('is-hidden');
            play_handle.classList.remove('is-hidden');

            container.dataset.videoActive = 'false';
        }
    }

    document.querySelectorAll('.sse-status-image-handle-prev').forEach(function(prev_handle)
    {
        prev_handle.addEventListener('click', function (e)
        {
            e.preventDefault();

            let target = getParentWithTagName(e.target, 'A');
            switchStatusImage(target.parentElement, target, 'prev');
        });
    });

    document.querySelectorAll('.sse-status-image-handle-next').forEach(function(next_handle)
    {
        next_handle.addEventListener('click', function (e)
        {
            e.preventDefault();

            let target = getParentWithTagName(e.target, 'A');
            switchStatusImage(target.parentElement, target, 'next');
        });
    });

    document.querySelectorAll('.sse-status-image.sse-status-image-video, .sse-status-image.sse-status-image-animated-gif').forEach(function(video_container)
    {
        video_container.addEventListener('click', function (e)
        {
            if (getParentWithClasses(e.target, ['sse-status-image-handle-prev', 'sse-status-image-handle-next']) !== null) return;

            e.preventDefault();
            handleVideo(getParentWithTagName(e.target, 'DIV'));
        });
    });

    document.querySelectorAll('.sse-status-content-is-hidden').forEach(function(element)
    {
        element.classList.add('is-hidden');
    });

    document.querySelectorAll('.sse-spoiler-text-toggle-handle').forEach(function(button)
    {
        button.addEventListener('click', function(e)
        {
            let button = e.target;
            let content = getParentWithTagName(e.target, 'P').nextElementSibling;

            e.preventDefault();

            // Show content
            if (content.classList.contains('is-hidden'))
            {
                content.classList.remove('is-hidden');
                button.innerHTML = button.dataset.hide;
            }

            // Hide content
            else
            {
                content.classList.add('is-hidden');
                button.innerHTML = button.dataset.show;
            }
        });
    });

    document.querySelectorAll('.sse-has-sensitive-content').forEach(function(element)
    {
        element.addEventListener('click', function(e)
        {
            if (getParentWithClasses(e.target, ['sse-status-image-handle-prev', 'sse-status-image-handle-next']) !== null) return;

            e.preventDefault();

            let figure = getParentWithTagName(e.target, 'figure');
            if (figure.classList.contains('is-blurred'))
                figure.classList.remove('is-blurred');
            else
                figure.classList.add('is-blurred');
        });
    });
});
