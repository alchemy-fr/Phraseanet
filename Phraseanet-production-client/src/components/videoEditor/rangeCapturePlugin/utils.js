const formatMilliseconds = (currentTime, frameRate) => {
    let hours = 0;
    let minutes = 0;
    let seconds = 0;
    let currentFrames = 0;
    if (currentTime > 0) {
        hours = Math.floor(currentTime / 3600);
        let s = currentTime - hours * 3600;
        minutes = Math.floor(s / 60);
        seconds = Math.floor(s - minutes * 60);
        let currentRest = currentTime - (Math.floor(currentTime));
        currentFrames = Math.round(frameRate * currentRest);
    }
    return {
        hours: hours,
        minutes: minutes,
        seconds: seconds,
        frames: currentFrames
    }
}

const formatTime = (currentTime, format, frameRate) => {
    frameRate = frameRate || 24;
    let hours = 0;
    let minutes = 0;
    let seconds = 0;
    let milliseconds = 0;
    let frames = 0;
    if (currentTime > 0) {
        hours = Math.floor(currentTime / 3600);
        let s = currentTime - hours * 3600;
        minutes = Math.floor(s / 60);
        seconds = Math.floor(s - minutes * 60);
        // keep only milliseconds rest ()
        milliseconds = (currentTime - (Math.floor(currentTime))).toFixed(3);
        frames = Math.round(frameRate * milliseconds);
        // if( currentFrames >= )
    }
    switch (format) {
        // standard vtt format
        case 'hh:mm:ss.mmm':
            return ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2) + '.' + ('00' + milliseconds).slice(-3) + '';
        case 'hms':
            let formatedOutput = [];
            if (hours > 0) {
                formatedOutput.push(('0' + hours).slice(-2) + 'h');
            }

            formatedOutput.push(('0' + minutes).slice(-2) + 'm');
            formatedOutput.push(('0' + seconds).slice(-2) + 's');

            return formatedOutput.join(' ');
        case '':
        default:
            return ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2) + 's ' + ('0' + frames).slice(-2) + 'f';

    }
}

const formatToFixedDecimals = (currentTime, decimalsPoints = 2) => {
    return parseFloat(currentTime.toFixed(decimalsPoints));
}

export {formatMilliseconds, formatTime, formatToFixedDecimals}
