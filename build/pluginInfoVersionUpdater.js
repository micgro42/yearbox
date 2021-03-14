module.exports = {
    readVersion( contents ) {
        return ''; // The plugin.info.txt does not contain a semantic version
    },

    writeVersion( contents ) {
        const options = contents
            .trim()
            .split("\n")
            .map(line => {
                const indexOfFirstSpace = line.indexOf(' ');
                return [line.slice(0, indexOfFirstSpace), line.slice(indexOfFirstSpace + 1)]
                    .map( piece => piece.trim()) })
            .reduce( (carry, [key, value]) => { return { ...carry, [key]: value};}, {} );

        options.date = (new Date()).toISOString().substr(0, 10);

        const longestKey = Object.keys(options).reduce( (carry, key) => Math.max(carry, key.length), 0);
        return Object.entries(options)
            .map( ([key, value]) => key.padEnd(longestKey) + ' ' + value)
            .join("\n");
    },
};
